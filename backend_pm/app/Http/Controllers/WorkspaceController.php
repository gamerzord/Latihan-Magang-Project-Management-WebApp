<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workspace;
use App\Models\User;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $workspaces = $request->user()
            ->workspaces()
            ->with(['creator'])
            ->withCount('boards')
            ->get();

        return response()->json([
            'workspaces' => $workspaces, 
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:private,workspace,public',
        ]);

        $data['created_by'] = $request->user()->id;

        $workspace = Workspace::create($data);

        $workspace->members()->attach($request->user()->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return response()->json([
            'workspace' => $workspace->load('creator')
        ], 201);
    }

    public function show($id)
    {
        $workspace = Workspace::with(['creator', 'members' => function ($query) {
            $query->withPivot('role', 'invited_by', 'joined_at');
        }, 'boards'])
            ->withCount('boards')
            ->find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        if ($workspace->visibility !== 'public' && !$workspace->isMember(auth()->id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'workspace' => $workspace
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $workspace = Workspace::find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        if (!$workspace->hasRole(auth()->id(), ['owner', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'visibility' => 'sometimes|required|in:private,workspace,public',
        ]);

        $workspace->update($data);

        return response()->json([
            'workspace' => $workspace->fresh(['creator', 'members'])
        ], 200);
    }

    public function destroy($id)
    {
        $workspace = Workspace::find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        if (!$workspace->hasRole(auth()->id(), ['owner'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workspace->delete();

        return response()->json(['message' => 'Workspace deleted successfully'], 200);
    }

    public function addMember(Request $request, $id)
    {
        $workspace = Workspace::find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        if (!$workspace->hasRole(auth()->id(), ['owner', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,admin,guest',
        ]);

        if ($workspace->isMember($data['user_id'])) {
            return response()->json(['message' => 'User is already a member'], 422);
        }

        $workspace->members()->attach($data['user_id'], [
            'role' => $data['role'],
            'invited_by' => auth()->id(),
            'joined_at' => now(),
        ]);

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $workspace->members()->where('user_id', $data['user_id'])->first()
        ], 200);
    }

    public function removeMember($id, $userId)
    {
        $workspace = Workspace::find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        if (!$workspace->hasRole(auth()->id(), ['owner', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($workspace->hasRole($userId, ['owner'])) {
            $ownerCount = $workspace->members()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return response()->json(['message' => 'Cannot remove the only owner of the workspace'], 422);
            }
        }

        if ($workspace->hasRole($userId, ['owner']) && !$workspace->hasRole(auth()->id(), ['owner'])) {
            return response()->json(['message' => 'Only owners can remove other owners'], 403);
        }

        $workspace->members()->detach($userId);

        return response()->json(['message' => 'Member removed successfully'], 200);
    }

    public function updateMemberRole(Request $request, $id, $userId)
    {
        $workspace = Workspace::find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        if (!$workspace->hasRole(auth()->id(), ['owner', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'role' => 'required|in:member,admin,owner,guest',
        ]);

        if (!$workspace->isMember($userId)) {
            return response()->json(['message' => 'User is not a member of this workspace'], 404);
        }

        if ($data['role'] === 'owner' && !$workspace->hasRole(auth()->id(), ['owner'])) {
            return response()->json(['message' => 'Only owners can assign owner role'], 403);
        }

        if ($workspace->hasRole($userId, ['owner']) && $data['role'] !== 'owner') {
            $ownerCount = $workspace->members()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return response()->json(['message' => 'Cannot demote the only owner of the workspace'], 422);
            }
        }

        $workspace->members()->updateExistingPivot($userId, [
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'Member role updated successfully',
            'member' => $workspace->members()->where('user_id', $userId)->first()
        ], 200);
    }

    public function leave($id)
    {
        $workspace = Workspace::find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        $userId = auth()->id();

        if (!$workspace->isMember($userId)) {
            return response()->json(['message' => 'You are not a member of this workspace'], 422);
        }

        if ($workspace->hasRole($userId, ['owner'])) {
            $ownerCount = $workspace->members()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return response()->json(['message' => 'Cannot leave workspace as the only owner. Transfer ownership first or delete the workspace.'], 422);
            }
        }

        $workspace->members()->detach($userId);

        return response()->json(['message' => 'Successfully left the workspace'], 200);
    }

    public function myMembership($id)
    {
        $workspace = Workspace::with(['creator', 'members'])
            ->withCount('boards')
            ->find($id);

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        $userMembership = $workspace->members()
            ->where('user_id', auth()->id())
            ->first();

        if (!$userMembership) {
            return response()->json(['message' => 'You are not a member of this workspace'], 403);
        }

        return response()->json([
            'workspace' => $workspace,
            'membership' => [
                'role' => $userMembership->pivot->role,
                'joined_at' => $userMembership->pivot->joined_at,
            ]
        ], 200);
    }

    public function availableMembers($workspaceId)
{
    $workspace = Workspace::findOrFail($workspaceId);
    
    $currentUserId = auth()->id();
    
    $existingMemberIds = $workspace->members()->pluck('users.id')->toArray();

    $allexcludeIds = array_merge($existingMemberIds, [$currentUserId]);
    
    $availableUsers = User::whereNotIn('id', $allexcludeIds)
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'avatar_url']);
    
    return response()->json(['members' => $availableUsers]);
}
}