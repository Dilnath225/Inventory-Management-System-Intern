<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an audit entry for any model action
     *
     * @param string $action The action performed (created, updated, deleted, etc.)
     * @param object $model The Eloquent model being audited
     * @param array|null $oldValues Previous values before the change
     * @param array|null $newValues New values after the change
     */
    public static function log(string $action, $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::create([
            'user_id'         => Auth::id(),
            'action'          => $action,
            'auditable_type'  => get_class($model),
            'auditable_id'    => $model->id,
            'old_values'      => $oldValues,
            'new_values'      => $newValues,
            'ip_address'      => Request::ip(),
            'created_at'      => now(),
        ]);
    }

    /**
     * Log a creation event - captures all new values
     */
    public static function logCreated($model): void
    {
        // Filter out sensitive/internal fields
        $values = collect($model->getAttributes())
            ->except(['password', 'remember_token', 'updated_at', 'created_at'])
            ->toArray();

        self::log('created', $model, null, $values);
    }

    /**
     * Log an update event - captures changed fields with old and new values
     */
    public static function logUpdated($model, array $originalValues): void
    {
        $changes = $model->getChanges();
        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $newValue) {
            if (in_array($key, ['updated_at', 'created_at', 'password', 'remember_token'])) {
                continue;
            }
            $oldValues[$key] = $originalValues[$key] ?? null;
            $newValues[$key] = $newValue;
        }

        if (!empty($newValues)) {
            self::log('updated', $model, $oldValues, $newValues);
        }
    }

    /**
     * Log a deletion event
     */
    public static function logDeleted($model): void
    {
        $values = collect($model->getAttributes())
            ->except(['password', 'remember_token', 'updated_at', 'created_at'])
            ->toArray();

        self::log('deleted', $model, $values, null);
    }

    /**
     * Log a quantity change specifically
     */
    public static function logQuantityChanged($model, int $oldQuantity, int $newQuantity): void
    {
        self::log('quantity_changed', $model, 
            ['quantity' => $oldQuantity], 
            ['quantity' => $newQuantity]
        );
    }

    /**
     * Log a status change specifically
     */
    public static function logStatusChanged($model, string $oldStatus, string $newStatus): void
    {
        self::log('status_changed', $model,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    /**
     * Log a borrowing event
     */
    public static function logBorrowed($borrowing, $item): void
    {
        self::log('borrowed', $item, null, [
            'borrowing_id' => $borrowing->id,
            'borrower_name' => $borrowing->borrower_name,
            'quantity_borrowed' => $borrowing->quantity_borrowed,
        ]);
    }

    /**
     * Log a return event
     */
    public static function logReturned($borrowing, $item): void
    {
        self::log('returned', $item, null, [
            'borrowing_id' => $borrowing->id,
            'borrower_name' => $borrowing->borrower_name,
            'quantity_returned' => $borrowing->quantity_borrowed,
        ]);
    }
}
