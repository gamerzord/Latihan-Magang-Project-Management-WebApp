<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $workspaceId = $request->query('workspace_id');

        if ($workspaceId) {
            $boards = Board::query()
                ->where('workspace_id', $workspaceId)
                ->where(function ($q) use ($user) {
                    $q->where('visibility', 'public')
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('user_id', $user->id);
                    })
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('workspace.members', function ($wm) use ($user) {
                        $wm->where('user_id', $user->id);
                    });
                })
                ->with(['creator', 'workspace'])
                ->get();

        } else {
            $boards = $user->boards()
                ->with(['creator', 'workspace'])
                ->get();
        }

        return response()->json([
            'boards' => $boards
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'background_type' => 'required|in:color,image',
            'background_value' => 'nullable|string',
            'visibility' => 'required|in:private,workspace,public',
        ]);

        $data['created_by'] = $request->user()->id;

        $board = Board::create($data);

        $board->members()->attach($request->user()->id, [
            'role' => 'admin',
        ]);

        return response()->json([
            'board' => $board->load(['creator', 'workspace'])
        ], 201);
    }

    public function show($id)
    {
        $board = Board::with([
            'workspace',
            'creator',
            'members',
            'lists.cards.labels',
            'lists.cards.members',
            'lists.cards.checklists.items',
            'labels'
        ])->find($id);

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }

        $userId = auth()->id();
        $workspace = $board->workspace;

        $allowed = false;

        if ($board->visibility === 'public') {
            $allowed = true;
        }

        elseif ($board->visibility === 'private') {
            $allowed = $board->isMember($userId);
        }

        elseif ($board->visibility === 'workspace') {
            $allowed =
                $workspace->members()->where('user_id', $userId)->exists() ||
                $board->isMember($userId);
        }

        if (!$allowed) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'board' => $board
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin') && !$board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'background_type' => 'sometimes|required|in:color,image',
            'background_value' => 'nullable|string',
            'visibility' => 'sometimes|required|in:private,workspace,public',
        ]);

        $board->update($data);

        return response()->json([
            'board' => $board->fresh()
        ], 200);
    }

    public function destroy($id)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $board->delete();

        return response()->json([
            'message' => 'Board deleted successfully'
        ], 200);
    }

    public function addMember(Request $request, $id)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,admin',
        ]);

        if ($board->isMember($data['user_id'])) {
            return response()->json([
                'message' => 'User is already a member of this board'
            ], 422);
        }

        $board->members()->attach($data['user_id'], [
            'role' => $data['role'],
            'added_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $board->members()->where('user_id', $data['user_id'])->first()
        ], 200);
    }

    public function removeMember($id, $userId)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($board->hasRole($userId, 'admin')) {
            $adminCount = $board->members()->wherePivot('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Cannot remove the only admin of the board'], 422);
            }
        }

        $board->members()->detach($userId);

        return response()->json([
            'message' => 'Member removed successfully'
        ], 200);
    }

    public function leave($id)
{
    $board = Board::find($id);

    if (!$board) {
        return response()->json(['message' => 'Board not found'], 404);
    }

    $userId = auth()->id();

    if (!$board->isMember($userId)) {
        return response()->json(['message' => 'You are not a member of this board'], 422);
    }

    if ($board->hasRole($userId, 'admin')) {
        $adminCount = $board->members()->wherePivot('role', 'admin')->count();
        if ($adminCount <= 1) {
            return response()->json([
                'message' => 'Cannot leave the board as the only admin. Transfer admin role to someone else first.'
            ], 422);
        }
    }

    $board->members()->detach($userId);

    return response()->json([
        'message' => 'Successfully left the board'
    ], 200);
}

    public function updateMemberRole(Request $request, $id, $userId)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'role' => 'required|in:member,admin',
        ]);

        if (!$board->isMember($userId)) {
            return response()->json([
                'message' => 'User is not a member of this board'
            ], 404);
        }

        if ($board->hasRole($userId, 'admin') && $data['role'] !== 'admin') {
            $adminCount = $board->members()->wherePivot('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'Cannot demote the only admin of the board'
                ], 422);
            }
        }

        $board->members()->updateExistingPivot($userId, [
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'Member role updated successfully',
            'member' => $board->members()->where('user_id', $userId)->first()
        ], 200);
    }

    public function availableMembers($boardId)
    {
        $board = Board::findOrFail($boardId);
        
        $currentUserId = auth()->id();
        
        $existingMemberIds = $board->members()->pluck('users.id')->toArray();

        $allexcludeIds = array_merge($existingMemberIds, [$currentUserId]);
        
        $workspaceMembers = $board->workspace->members()
            ->whereNotIn('users.id', $allexcludeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar_url']);
        
        return response()->json(['members' => $workspaceMembers]);
    }
}