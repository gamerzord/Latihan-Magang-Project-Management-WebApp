<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'visibility',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role', 'invited_by', 'joined_at')
            ->withTimestamps();
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function isMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function hasRole($userId, $roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        return $this->members()
            ->where('user_id', $userId)
            ->whereIn('workspace_members.role', $roles)
            ->exists();
    }

    public function getUserRole($userId)
    {
        $member = $this->members()
            ->where('user_id', $userId)
            ->first();

        return $member ? $member->pivot->role : null;
    }
}