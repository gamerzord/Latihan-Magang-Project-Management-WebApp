<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'title',
        'description',
        'background_type',
        'background_value',
        'visibility',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'board_members')
            ->withPivot('role', 'added_by')
            ->withTimestamps();
    }

    public function lists()
    {
        return $this->hasMany(BoardList::class)->orderBy('position');
    }

    public function labels()
    {
        return $this->hasMany(Label::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function isMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function canUserAccess($userId)
    {
        switch ($this->visibility) {
            case 'public':
                return true;
                
            case 'workspace':
                return $this->workspace->isMember($userId) 
                    || $this->members()->where('user_id', $userId)->exists();
                
            case 'private':
                return $this->members()->where('user_id', $userId)->exists();
                
            default:
                return false;
        }
    }

    public function hasRole($userId, $role)
    {
        $roles = is_array($role) ? $role : [$role];
        return $this->members()
            ->where('user_id', $userId)
            ->wherePivot('board_members.role', $role)
            ->exists();
    }
}
