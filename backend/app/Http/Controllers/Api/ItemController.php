<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    /**
     * List all items with filtering and search
     */
    public function index(Request $request)
    {
        $query = Item::with(['place.cupboard', 'creator:id,name']);

        // apply text search if a keyword is provided
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // optionally filter the items by their current status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // narrow down to a specific place if requested
        if ($request->has('place_id') && $request->place_id) {
            $query->where('place_id', $request->place_id);
        }

        $items = $query->orderBy('created_at', 'desc')->get();

        return response()->json($items);
    }

    /**
     * Create a new item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:255|unique:items,code',
            'quantity'      => 'required|integer|min:0',
            'serial_number' => 'nullable|string|max:255',
            'image'         => 'nullable|image|max:2048',
            'description'   => 'nullable|string',
            'place_id'      => 'required|exists:places,id',
            'status'        => 'sometimes|in:in_store,borrowed,damaged,missing',
        ]);

        // process the uploaded file and store it securely in the public disk
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('items', 'public');
        }

        $item = Item::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        AuditService::logCreated($item);

        return response()->json([
            'message' => 'Item created successfully',
            'item'    => $item->load(['place.cupboard', 'creator:id,name']),
        ], 201);
    }

    /**
     * Show item detail with borrowing history
     */
    public function show(Item $item)
    {
        $item->load(['place.cupboard', 'creator:id,name', 'borrowings.creator:id,name']);

        // include dynamic attributes like available quantity for the frontend
        $item->append('available_quantity');

        return response()->json($item);
    }

    /**
     * Update an item
     */
    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'code'          => ['sometimes', 'string', 'max:255', Rule::unique('items')->ignore($item->id)],
            'quantity'      => 'sometimes|integer|min:0',
            'serial_number' => 'nullable|string|max:255',
            'image'         => 'nullable|image|max:2048',
            'description'   => 'nullable|string',
            'place_id'      => 'sometimes|exists:places,id',
        ]);

        $original = $item->getAttributes();

        // process new image upload if the user provided one
        if ($request->hasFile('image')) {
            // clean up the old image file to save storage space
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $validated['image'] = $request->file('image')->store('items', 'public');
        }

        $item->update($validated);

        AuditService::logUpdated($item, $original);

        return response()->json([
            'message' => 'Item updated successfully',
            'item'    => $item->fresh()->load(['place.cupboard', 'creator:id,name']),
        ]);
    }

    /**
     * Delete an item
     */
    public function destroy(Item $item)
    {
        // ensure we also remove the associated image file from disk
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }

        AuditService::logDeleted($item);
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully',
        ]);
    }

    /**
     * Increment or decrement item quantity
     * Uses database transactions with row locking for concurrency safety
     */
    public function updateQuantity(Request $request, Item $item)
    {
        $validated = $request->validate([
            'action' => 'required|in:increment,decrement',
            'amount' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($item, $validated) {
            // using a pessimistic lock to ensure stock doesn't get messed up during concurrent requests
            $item = Item::lockForUpdate()->find($item->id);

            $oldQuantity = $item->quantity;

            if ($validated['action'] === 'increment') {
                $newQuantity = $oldQuantity + $validated['amount'];
            } else {
                $newQuantity = $oldQuantity - $validated['amount'];

                if ($newQuantity < 0) {
                    return response()->json([
                        'message' => 'Insufficient quantity. Current stock: ' . $oldQuantity,
                    ], 422);
                }
            }

            $item->update(['quantity' => $newQuantity]);

            // keep the overall status in sync with the current available stock
            if ($newQuantity === 0 && $item->status === 'in_store') {
                $oldStatus = $item->status;
                $item->update(['status' => 'missing']);
                AuditService::logStatusChanged($item, $oldStatus, 'missing');
            } elseif ($newQuantity > 0 && $item->status === 'missing') {
                $oldStatus = $item->status;
                $item->update(['status' => 'in_store']);
                AuditService::logStatusChanged($item, $oldStatus, 'in_store');
            }

            AuditService::logQuantityChanged($item, $oldQuantity, $newQuantity);

            return response()->json([
                'message'      => 'Quantity updated successfully',
                'item'         => $item->fresh()->load(['place.cupboard']),
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
            ]);
        });
    }

    /**
     * Change item status with validation of allowed transitions
     */
    public function updateStatus(Request $request, Item $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:in_store,borrowed,damaged,missing',
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $item->status;

        // check if this state change is actually allowed based on our rules
        if (!$item->canTransitionTo($newStatus)) {
            return response()->json([
                'message' => "Cannot transition from '{$oldStatus}' to '{$newStatus}'. Allowed transitions: " .
                    implode(', ', Item::STATUS_TRANSITIONS[$oldStatus] ?? []),
            ], 422);
        }

        $item->update(['status' => $newStatus]);

        AuditService::logStatusChanged($item, $oldStatus, $newStatus);

        return response()->json([
            'message'    => 'Status updated successfully',
            'item'       => $item->fresh()->load(['place.cupboard']),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }
}
