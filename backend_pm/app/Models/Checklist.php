<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'title',
        'position',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function items()
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    public function getProgressAttribute()
    {
        $total = $this->items()->count();
        $completed = $this->items()->where('completed', true)->count();
        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round($completed / $total * 100) : 0,
        ];
    }
}
