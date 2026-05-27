<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cupboard;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CupboardController extends Controller
{
    /**
     * List all cupboards with places count
     */
    public function index()
    {
        $cupboards = Cupboard::withCount('places')
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($cupboards);
    }

    /**
     * Create a new cupboard
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $cupboard = Cupboard::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        AuditService::logCreated($cupboard);

        return response()->json([
            'message'  => 'Cupboard created successfully',
            'cupboard' => $cupboard->load('creator:id,name'),
        ], 201);
    }

    /**
     * Show cupboard with its places and items
     */
    public function show(Cupboard $cupboard)
    {
        $cupboard->load(['places.items', 'creator:id,name']);

        return response()->json($cupboard);
    }

    /**
     * Update a cupboard
     */
    public function update(Request $request, Cupboard $cupboard)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $original = $cupboard->getAttributes();
        $cupboard->update($validated);

        AuditService::logUpdated($cupboard, $original);

        return response()->json([
            'message'  => 'Cupboard updated successfully',
            'cupboard' => $cupboard->fresh()->load('creator:id,name'),
        ]);
    }

    /**
     * Delete a cupboard (cascades to places and items)
     */
    public function destroy(Cupboard $cupboard)
    {
        AuditService::logDeleted($cupboard);
        $cupboard->delete();

        return response()->json([
            'message' => 'Cupboard deleted successfully',
        ]);
    }
}
