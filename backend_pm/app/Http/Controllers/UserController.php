<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'exclude' => 'nullable|string',
        ]);

        $query = $request->input('q', '');
        $excludeIds = $request->input('exclude') 
            ? array_map('intval', explode(',', $request->input('exclude')))
            : [];

        
        $currentUserId = auth()->id();
        $excludeIds[] = $currentUserId;

        $users = User::query()
            ->where('id', '!=', $currentUserId)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->when(!empty($excludeIds), function ($q) use ($excludeIds) {
                $q->whereNotIn('id', $excludeIds);
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'avatar_url']);

        return response()->json(['users' => $users]);
    }
}