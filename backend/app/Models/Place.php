<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cupboard_id',
        'created_by',
    ];

    /**
     * The cupboard this place belongs to
     */
    public function cupboard()
    {
        return $this->belongsTo(Cupboard::class);
    }

    /**
     * Creator of this place
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Items stored in this place
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
