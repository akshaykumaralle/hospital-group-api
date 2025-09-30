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
        $group = Group::with('children')->findOrFail($id);
        
        return response()->json($group);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $group = Group::findOrFail($id);
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

        $group->update($validated);

        return response()->json($group);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $group = Group::findOrFail($id);
        $group->delete();
        
        return response()->json(['message' => 'Group deleted successfully']);
    }
}