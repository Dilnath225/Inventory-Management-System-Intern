<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cupboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    /**
     * Creator of this cupboard
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Places inside this cupboard
     */
    public function places()
    {
        return $this->hasMany(Place::class);
    }

    /**
     * Get total items count across all places in this cupboard
     */
    public function getItemsCountAttribute()
    {
        return $this->places->sum(function ($place) {
            return $place->items->count();
        });
    }
}
