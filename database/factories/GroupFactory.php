<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company . ' Clinician Group',
            'description' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['clinician_group', 'clinician_group', 'clinician_group', 'hospital']),
            'parent_id' => null, // Default to no parent
        ];
    }
}