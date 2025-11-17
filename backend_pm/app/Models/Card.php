<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'title',
        'description',
        'position',
        'due_date',
        'due_date_completed',
        'archived',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'due_date_completed' => 'boolean',
        'archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function list()
    {
        return $this->belongsTo(BoardList::class, 'list_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'card_labels')->withTimestamps();
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'card_members')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class)->orderBy('position');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
