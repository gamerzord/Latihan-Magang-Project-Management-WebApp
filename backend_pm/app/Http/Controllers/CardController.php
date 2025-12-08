<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;

class CardController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'list_id' => 'required|exists:lists,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $list = BoardList::with('board')->find($data['list_id']);

        if (!$list) {
            return response()->json([
                'message' => 'List not found'
            ], 404);
        }

        if (!$list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $maxPosition = Card::where('list_id', $data['list_id'])->max('position') ?? -1;

        $data['position'] = $maxPosition + 1;
        $data['created_by'] = auth()->id();

        $card = Card::create($data);

        return response()->json([
            'card' => $card->load(['labels', 'members', 'checklists'])
        ], 201);
    }

    public function show($id)
    {
        $card = Card::with([
            'list.board',
            'creator',
            'labels',
            'members',
            'checklists.items.assignee',
            'attachments.uploader',
            'comments.user'
        ])->find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        if (!$card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'card' => $card
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $card = Card::with('list.board')->find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        if (!$card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_date_completed' => 'sometimes|boolean',
            'archived' => 'sometimes|boolean',
            'position' => 'sometimes|integer|min:0',
            'list_id' => 'sometimes|exists:lists,id',
        ]);

        $card->update($data);

        return response()->json([
            'card' => $card->load(['labels', 'members', 'checklists'])
        ]);
    }

    public function destroy($id)
    {
        $card = Card::with('list.board')->find($id);

        if (!$card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        if (!$card->list->board->isMember(auth()->id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $card->delete();

        return response()->json([
            'message' => 'Card deleted'
        ], 200);
    }

    public function move(Request $request, $id)
    {
        $card = Card::find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        $data = $request->validate([
            'list_id' => 'required|exists:lists,id',
            'position' => 'required|integer|min:0',
        ]);

        $card->update($data);

        return response()->json([
            'card' => $card->fresh()
        ]);
    }

    public function addLabel(Request $request, $id)
    {
        $card = Card::find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        $data = $request->validate([
            'label_id' => 'required|exists:labels,id',
        ]);

        if (!$card->labels()->where('label_id', $data['label_id'])->exists()) {
            $card->labels()->attach($data['label_id']);
        }

        $label = Label::find($data['label_id']);

        return response()->json([
            'label' => $label,
        ]);
    }

    public function removeLabel($id, $labelId)
    {
        $card = Card::find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        $card->labels()->detach($labelId);

        return response()->json([
            'card' => $card->load('labels')
        ]);
    }

public function addMember(Request $request, $id)
{
    $card = Card::find($id);
    
    if (!$card) {
        return response()->json([
            'message' => 'Card not found'
        ], 404);
    }
    
    $board = $card->list->board;
    
    if (!$board->isMember(auth()->id())) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);
    
    if ($card->members()->where('user_id', $data['user_id'])->exists()) {
        return response()->json([
            'message' => 'User is already a member of this card'
        ], 422);
    }
    
    $card->members()->attach($data['user_id'], [
        'assigned_by' => auth()->id(),
    ]);
    
    $member = $card->members()->where('user_id', $data['user_id'])->first();
    
    return response()->json([
        'message' => 'Member added successfully',
        'member' => $member
    ], 200);
}

    public function removeMember($id, $userId)
    {
        $card = Card::find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        $board = $card->board;

        if (!$board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $card->members()->detach($userId);

        return response()->json([
            'message' => 'Member removed successfully'
        ], 200);
    }

    public function archive($id)
    {
        $card = Card::with('list.board')->find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        if (!$card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $card->update(['archived' => true]);

        return response()->json([
            'message' => 'Card archived successfully',
            'card' => $card->fresh()
        ]);
    }

    public function restore($id)
    {
        $card = Card::with('list.board')->find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        if (!$card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $card->update(['archived' => false]);

        return response()->json([
            'message' => 'Card restored successfully',
            'card' => $card->fresh()
        ]);
    }

    public function toggleDueDateCompletion($id)
    {
        $card = Card::with('list.board')->find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Card not found'
            ], 404);
        }

        if (!$card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $card->update([
            'due_date_completed' => !$card->due_date_completed
        ]);

        return response()->json([
            'message' => 'Due date completion updated',
            'card' => $card->fresh()
        ]);
    }

        public function availableMembers($cardId)
    {
        $card = Card::findOrFail($cardId);
        $board = $card->list->board;
        
        $currentUserId = auth()->id();
        
        $existingCardMemberIds = $card->members()->pluck('users.id')->toArray();
        
        $boardMembers = $board->members()
            ->whereNotIn('users.id', $existingCardMemberIds)
            ->where('users.id', '!=', $currentUserId)
            ->orderBy('name')
            ->get(['users.id', 'name', 'email', 'avatar_url']);
        
        return response()->json(['members' => $boardMembers]);
    }
}