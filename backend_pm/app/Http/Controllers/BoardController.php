<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        
        if ($workspaceId) {
            $boards = Board::where('workspace_id', $workspaceId)
                ->with(['creator', 'workspace'])
                ->get();
        } else {
            $boards = $request->user()->boards()
                ->with(['creator', 'workspace'])
                ->get();
        }

        return response()->json($boards);
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

        return response()->json($board->load(['creator', 'workspace']), 201);
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

        if (!$board->isMember(auth()->id()) && $board->visibility !== 'public') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($board);
    }

    public function update(Request $request, $id)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin') && !$board->isMember(auth()->id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'background_type' => 'sometimes|required|in:color,image',
            'background_value' => 'nullable|string',
            'visibility' => 'sometimes|required|in:private,workspace,public',
        ]);

        $board->update($data);

        return response()->json($board->fresh());
    }

    public function destroy($id)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $board->delete();

        return response()->json(['message' => 'Board deleted']);
    }

    public function addMember(Request $request, $id)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,admin',
        ]);

        if ($board->isMember($data['user_id'])) {
            return response()->json(['message' => 'User is already a member of this board'], 422);
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

        return response()->json(['message' => 'Member removed successfully'], 200);
    }

    public function updateMemberRole(Request $request, $id, $userId)
    {
        $board = Board::find($id);

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }

        if (!$board->hasRole(auth()->id(), 'admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'role' => 'required|in:member,admin',
        ]);

        if (!$board->isMember($userId)) {
            return response()->json(['message' => 'User is not a member of this board'], 404);
        }

        if ($board->hasRole($userId, 'admin') && $data['role'] !== 'admin') {
            $adminCount = $board->members()->wherePivot('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Cannot demote the only admin of the board'], 422);
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
}