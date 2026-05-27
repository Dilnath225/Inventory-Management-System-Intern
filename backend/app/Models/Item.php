<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'quantity',
        'serial_number',
        'image',
        'description',
        'place_id',
        'status',
        'created_by',
    ];

    /**
     * Valid status transitions map
     * Defines which statuses can transition to which other statuses
     */
    public const STATUS_TRANSITIONS = [
        'in_store' => ['borrowed', 'damaged', 'missing'],
        'borrowed' => ['in_store', 'damaged', 'missing'],
        'damaged'  => ['in_store', 'missing'],
        'missing'  => ['in_store'],
    ];

    /**
     * The place where this item is stored
     */
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Creator of this item
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Borrowings for this item
     */
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    /**
     * Active borrowings for this item
     */
    public function activeBorrowings()
    {
        return $this->hasMany(Borrowing::class)->where('status', 'active');
    }

    /**
     * Check if a status transition is valid
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $currentStatus = $this->status;
        return isset(self::STATUS_TRANSITIONS[$currentStatus])
            && in_array($newStatus, self::STATUS_TRANSITIONS[$currentStatus]);
    }

    /**
     * Get available quantity (total minus actively borrowed)
     */
    public function getAvailableQuantityAttribute(): int
    {
        $borrowed = $this->activeBorrowings()->sum('quantity_borrowed');
        return max(0, $this->quantity - $borrowed);
    }
}
