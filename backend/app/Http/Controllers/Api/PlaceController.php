<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Services\AuditService;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    /**
     * List all places with cupboard info and item counts
     */
    public function index(Request $request)
    {
        $query = Place::withCount('items')
            ->with(['cupboard:id,name', 'creator:id,name']);

        // Optional filter by cupboard
        if ($request->has('cupboard_id')) {
            $query->where('cupboard_id', $request->cupboard_id);
        }

        $places = $query->orderBy('created_at', 'desc')->get();

        return response()->json($places);
    }

    /**
     * Create a new place
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'cupboard_id' => 'required|exists:cupboards,id',
        ]);

        $place = Place::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        AuditService::logCreated($place);

        return response()->json([
            'message' => 'Place created successfully',
            'place'   => $place->load(['cupboard:id,name', 'creator:id,name']),
        ], 201);
    }

    /**
     * Show place with items
     */
    public function show(Place $place)
    {
        $place->load(['items', 'cupboard:id,name', 'creator:id,name']);

        return response()->json($place);
    }

    /**
     * Update a place
     */
    public function update(Request $request, Place $place)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cupboard_id' => 'sometimes|exists:cupboards,id',
        ]);

        $original = $place->getAttributes();
        $place->update($validated);

        AuditService::logUpdated($place, $original);

        return response()->json([
            'message' => 'Place updated successfully',
            'place'   => $place->fresh()->load(['cupboard:id,name', 'creator:id,name']),
        ]);
    }

    /**
     * Delete a place (cascades to items)
     */
    public function destroy(Place $place)
    {
        AuditService::logDeleted($place);
        $place->delete();

        return response()->json([
            'message' => 'Place deleted successfully',
        ]);
    }
}
