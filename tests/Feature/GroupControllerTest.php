<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Group;

class GroupControllerTest extends TestCase
{
    // Ensure the database is reset before each test
    use RefreshDatabase;

    // --- CREATE (POST /api/groups) ---

    /** @test */
    public function a_group_can_be_created_with_valid_data()
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'Surgery Ward',
            'description' => 'Group for all surgery clinicians.',
            'type' => 'hospital',
            'parent_id' => null
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'name', 'description', 'parent_id', 'created_at']);

        $this->assertCount(1, Group::all());
        $this->assertDatabaseHas('groups', ['name' => 'Surgery Ward', 'parent_id' => null]);
    }

    /** @test */
    public function a_child_group_can_be_created_under_a_valid_parent()
    {
        // 1. Setup a Parent Group
        $hospital = Group::factory()->create(['name' => 'Main Hospital']);

        // 2. Attempt to create a Child Group
        $response = $this->postJson('/api/groups', [
            'name' => 'Emergency Clinicians',
            'description' => 'Group for children\'s specialists.',
            'type' => 'clinician_group',
            'parent_id' => $hospital->id
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('groups', ['name' => 'Emergency Clinicians', 'parent_id' => $hospital->id]);
    }

    /** @test */
    public function group_creation_fails_if_parent_id_does_not_exist()
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'Invalid Child',
            'parent_id' => 999 // Non-existent ID
        ]);

        $response->assertStatus(422) // Unprocessable Entity (Validation Error)
                 ->assertJsonValidationErrors('parent_id');
    }

    // --- READ (GET /api/groups and GET /api/groups/{id}) ---

    /** @test */
    public function all_groups_are_retrieved_structured_by_top_level_parents()
    {
        // Setup a multi-level hierarchy
        $hospitalA = Group::factory()->create(['name' => 'Hospital A', 'parent_id' => null]);
        $department1 = Group::factory()->create(['name' => 'Dept 1', 'parent_id' => $hospitalA->id]);
        $hospitalB = Group::factory()->create(['name' => 'Hospital B', 'parent_id' => null]);

        $response = $this->getJson('/api/groups');

        $response->assertStatus(200)
                 ->assertJsonCount(2); // Only top-level groups (A and B) at the root
                 
        // Check that Hospital A is present and has its children loaded
        $response->assertJsonFragment(['name' => 'Hospital A'])
                 ->assertJsonFragment(['name' => 'Hospital B']);
    }

    /** @test */
    public function retrieving_a_non_existent_group_returns_404()
    {
        $response = $this->getJson('/api/groups/999'); // ID that does not exist

        $response->assertStatus(404)
                 ->assertJson(['status' => 'error', 'message' => 'The requested group was not found.']);
    }

    // --- UPDATE (PUT /api/groups/{id}) ---

    /** @test */
    public function a_group_can_be_updated()
    {
        $group = Group::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson('/api/groups/' . $group->id, [
            'name' => 'New Name for Group',
            'description' => 'Updated info.'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'New Name for Group']);

        $this->assertDatabaseHas('groups', ['id' => $group->id, 'name' => 'New Name for Group']);
    }

    /** @test */
    public function a_group_cannot_be_its_own_parent_when_updating()
    {
        $group = Group::factory()->create();

        $response = $this->putJson('/api/groups/' . $group->id, [
            'parent_id' => $group->id // Attempting to set self as parent
        ]);

        $response->assertStatus(422) // Validation Error
                 ->assertJson(['status' => 'error', 'message' => 'A group cannot be its own parent.']);
    }

    // --- DELETE (DELETE /api/groups/{id}) ---

    /** @test */
    public function a_group_can_be_deleted_if_it_has_no_children()
    {
        $group = Group::factory()->create();

        $response = $this->deleteJson('/api/groups/' . $group->id);

        $response->assertStatus(200);
        $this->assertCount(0, Group::all());
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    /** @test */
    public function a_group_cannot_be_deleted_if_it_has_children_nodes()
    {
        // Setup Parent and Child
        $parentGroup = Group::factory()->create(['name' => 'Parent']);
        Group::factory()->create(['name' => 'Child', 'parent_id' => $parentGroup->id]);

        $response = $this->deleteJson('/api/groups/' . $parentGroup->id);

        $response->assertStatus(409) // Conflict: Data Integrity Violation
                 ->assertJson(['status' => 'error', 'message' => 'Cannot delete group with children.']);

        $this->assertCount(2, Group::all()); // Ensure neither group was deleted
    }
}