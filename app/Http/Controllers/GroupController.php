<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $groups = Group::with('children')->whereNull('parent_id')->get();

        return response()->json($groups);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('groups')->where(function ($query) use ($request) {
                    return $query->where('parent_id', $request->input('parent_id'));
                }),
            ],
            'description' => 'nullable|string',
            'type' => 'required|string|in:hospital,clinician_group',
            'parent_id' => 'nullable|exists:groups,id',
        ]);

        $group = Group::create($validated);

        return response()->json($group, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $group = Group::with('children')->find($id);
        if (!$group) {
            return response()->json(['status' => 'error', 'message' => 'The requested group was not found.'], 404);
        }

        return response()->json($group);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['status' => 'error', 'message' => 'The requested group was not found.'], 404);
        }

        $parentId = $request->input('parent_id', $group->parent_id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('groups')->where(function ($query) use ($parentId) {
                    return $query->where('parent_id', $parentId);
                })->ignore($id),
            ],
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:hospital,clinician_group',
            'parent_id' => 'nullable|exists:groups,id',
        ]);

        // Prevent circular reference: group cannot be its own parent or ancestor
        if (!empty($validated['parent_id'])) {
            if ($validated['parent_id'] == $group->id) {
                return response()->json(['status' => 'error', 'message' => 'A group cannot be its own parent.'], 422);
            }

            $parent = Group::find($validated['parent_id']);
            if ($parent) {
                $ancestor = $parent;
                while ($ancestor) {
                    if ($ancestor->id == $group->id) {
                        return response()->json(['status' => 'error', 'message' => 'A group cannot be its own ancestor.'], 422);
                    }
                    $ancestor = $ancestor->parent;
                }
            }
        }

        $group->update($validated);

        return response()->json($group);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['status' => 'error', 'message' => 'The requested group was not found.'], 404);
        }

        if ($group->children()->count() > 0) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete group with children.'], 409);
        }
        
        $group->delete();

        return response()->json(['status' => 'success', 'message' => 'Group deleted successfully']);
    }
}