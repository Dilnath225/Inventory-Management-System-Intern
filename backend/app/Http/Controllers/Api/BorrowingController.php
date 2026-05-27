<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Item;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    /**
     * List all borrowings
     */
    public function index(Request $request)
    {
        $query = Borrowing::with(['item:id,name,code,status', 'creator:id,name']);

        // only fetch borrowings matching the requested status (if any)
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // narrow down the history to a specific item
        if ($request->has('item_id') && $request->item_id) {
            $query->where('item_id', $request->item_id);
        }

        $borrowings = $query->orderBy('created_at', 'desc')->get();

        return response()->json($borrowings);
    }

    /**
     * Create a new borrowing
     * 
     * Business logic:
     * 1. Validate item exists and has sufficient stock
     * 2. Use DB transaction with row-level locking to prevent race conditions
     * 3. Reduce item quantity atomically
     * 4. Update item status to 'borrowed' if applicable
     * 5. Create borrowing record
     * 6. Log audit entries
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id'              => 'required|exists:items,id',
            'borrower_name'        => 'required|string|max:255',
            'contact_details'      => 'required|string|max:255',
            'borrow_date'          => 'required|date',
            'expected_return_date' => 'required|date|after_or_equal:borrow_date',
            'quantity_borrowed'    => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            // apply pessimistic locking to the item row so concurrent requests don't oversell stock
            $item = Item::lockForUpdate()->findOrFail($validated['item_id']);

            // safety check: prevent borrowing if the item is flagged as damaged or missing
            if (in_array($item->status, ['damaged', 'missing'])) {
                return response()->json([
                    'message' => "Cannot borrow an item with status '{$item->status}'.",
                ], 422);
            }

            // verify we have enough stock to fulfill this borrowing request
            $availableQty = $item->quantity;
            if ($validated['quantity_borrowed'] > $availableQty) {
                return response()->json([
                    'message' => "Insufficient stock. Available quantity: {$availableQty}",
                ], 422);
            }

            // decrement the item's available stock
            $oldQuantity = $item->quantity;
            $newQuantity = $oldQuantity - $validated['quantity_borrowed'];
            $item->update(['quantity' => $newQuantity]);

            // figure out the new status based on remaining stock
            $oldStatus = $item->status;
            if ($newQuantity === 0) {
                $item->update(['status' => 'borrowed']);
            } elseif ($item->status !== 'borrowed') {
                // mark as 'borrowed' even if we have some stock left, to show partial lending
                $item->update(['status' => 'borrowed']);
            }

            // persist the new borrowing record
            $borrowing = Borrowing::create([
                ...$validated,
                'status'     => 'active',
                'created_by' => auth()->id(),
            ]);

            // log all these changes for audit trails
            AuditService::logQuantityChanged($item, $oldQuantity, $newQuantity);
            if ($oldStatus !== $item->status) {
                AuditService::logStatusChanged($item, $oldStatus, $item->status);
            }
            AuditService::logBorrowed($borrowing, $item);
            AuditService::logCreated($borrowing);

            return response()->json([
                'message'   => 'Item borrowed successfully',
                'borrowing' => $borrowing->load(['item:id,name,code,status,quantity', 'creator:id,name']),
            ], 201);
        });
    }

    /**
     * Show a single borrowing
     */
    public function show(Borrowing $borrowing)
    {
        $borrowing->load(['item.place.cupboard', 'creator:id,name']);

        return response()->json($borrowing);
    }

    /**
     * Return a borrowed item
     * 
     * Business logic:
     * 1. Validate borrowing is active
     * 2. Use DB transaction with row-level locking
     * 3. Restore item quantity atomically
     * 4. Update item status back to 'in_store' if no other active borrowings
     * 5. Mark borrowing as returned
     * 6. Log audit entries
     */
    public function returnItem(Request $request, Borrowing $borrowing)
    {
        if ($borrowing->status !== 'active') {
            return response()->json([
                'message' => 'This borrowing has already been returned.',
            ], 422);
        }

        return DB::transaction(function () use ($borrowing) {
            // lock the item to avoid concurrency issues during return
            $item = Item::lockForUpdate()->findOrFail($borrowing->item_id);

            // put the returned quantity back into stock
            $oldQuantity = $item->quantity;
            $newQuantity = $oldQuantity + $borrowing->quantity_borrowed;
            $item->update(['quantity' => $newQuantity]);

            // mark this specific borrowing as finalized
            $borrowing->update([
                'status'             => 'returned',
                'actual_return_date' => now()->toDateString(),
            ]);

            // verify if any other users are still borrowing this item
            $activeBorrowings = Borrowing::where('item_id', $item->id)
                ->where('status', 'active')
                ->count();

            // if everything is returned, the item goes back to 'in_store'
            $oldStatus = $item->status;
            if ($activeBorrowings === 0) {
                $item->update(['status' => 'in_store']);
            }

            // keep a paper trail of the return action
            AuditService::logQuantityChanged($item, $oldQuantity, $newQuantity);
            if ($oldStatus !== $item->fresh()->status) {
                AuditService::logStatusChanged($item->fresh(), $oldStatus, $item->fresh()->status);
            }
            AuditService::logReturned($borrowing, $item);

            return response()->json([
                'message'   => 'Item returned successfully',
                'borrowing' => $borrowing->fresh()->load(['item:id,name,code,status,quantity', 'creator:id,name']),
            ]);
        });
    }
}
