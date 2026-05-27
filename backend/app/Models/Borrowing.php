<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'borrower_name',
        'contact_details',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'quantity_borrowed',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'expected_return_date' => 'date',
            'actual_return_date' => 'date',
        ];
    }

    /**
     * The item that was borrowed
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The user who created this borrowing record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if borrowing is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'active'
            && $this->expected_return_date->isPast();
    }
}
