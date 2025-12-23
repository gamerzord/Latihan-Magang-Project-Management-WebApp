<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use App\Models\User;
use App\Models\Board;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function createFromEmail(Request $request)
    {
        $validated = $request->validate([
            'board_id' => 'required|exists:boards,id',
            'list_id' => 'required|exists:lists,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'creator_email' => 'required|email',
            'assignees' => 'nullable|array',
            'assignees.*' => 'email',
            'labels' => 'nullable|array',
            'labels.*' => 'string',
            'attachments' => 'nullable|array',
            'attachments.*.url' => 'required|url',
            'attachments.*.filename' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Find the user who sent the email
            $creator = User::where('email', $validated['creator_email'])->first();
            
            if (!$creator) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found with email: ' . $validated['creator_email']
                ], 404);
            }

            // Check if user has access to the board
            $board = Board::with('members')->findOrFail($validated['board_id']);
            $hasAccess = $board->members->contains('id', $creator->id) || 
                        $board->creator_id === $creator->id;

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have access to this board'
                ], 403);
            }

            // Verify list belongs to board
            $list = BoardList::where('id', $validated['list_id'])
                           ->where('board_id', $validated['board_id'])
                           ->firstOrFail();

            // Get the next position
            $maxPosition = Card::where('list_id', $list->id)->max('position') ?? 0;

            // Create the card
            $card = Card::create([
                'list_id' => $list->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'position' => $maxPosition + 1,
                'due_date' => $validated['due_date'] ?? null,
                'created_by' => $creator->id,
            ]);

            // Add creator as a member
            $card->members()->attach($creator->id, [
                'assigned_by' => $creator->id
            ]);

            // Handle assignees
            if (!empty($validated['assignees'])) {
                foreach ($validated['assignees'] as $assigneeEmail) {
                    $assignee = User::where('email', $assigneeEmail)->first();
                    if ($assignee && $board->members->contains('id', $assignee->id)) {
                        $card->members()->syncWithoutDetaching([
                            $assignee->id => ['assigned_by' => $creator->id]
                        ]);
                    }
                }
            }

            // Handle labels
            if (!empty($validated['labels'])) {
                foreach ($validated['labels'] as $labelName) {
                    $label = $board->labels()->where('name', $labelName)->first();
                    if ($label) {
                        $card->labels()->attach($label->id);
                    }
                }
            }

            // Handle attachments (store metadata)
            if (!empty($validated['attachments'])) {
                foreach ($validated['attachments'] as $attachment) {
                    $card->attachments()->create([
                        'filename' => $attachment['filename'],
                        'file_path' => $attachment['url'],
                        'file_type' => pathinfo($attachment['filename'], PATHINFO_EXTENSION),
                        'file_size' => 0,
                        'uploaded_by' => $creator->id,
                    ]);
                }
            }

            // Log the activity manually (without spatie/laravel-activitylog)
            Log::info('Card created via email', [
                'card_id' => $card->id,
                'board_id' => $board->id,
                'creator_id' => $creator->id,
                'source' => 'email'
            ]);

            DB::commit();

            // Load relationships
            $card->load(['creator', 'members', 'labels', 'attachments', 'list']);

            return response()->json([
                'success' => true,
                'message' => 'Card created successfully from email',
                'data' => [
                    'card' => $card,
                    'board' => $board->title,
                    'list' => $list->title,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create card from email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create card from email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate email data before creating card (for n8n to check)
     */
    public function validateEmailData(Request $request)
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'board_title' => 'required|string',
            'list_title' => 'required|string',
            'creator_email' => 'required|email',
        ]);

        try {
            // Check if user exists
            $creator = User::where('email', $validated['creator_email'])->first();
            if (!$creator) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['User not found with this email']
                ]);
            }

            // Get workspace and check access
            $workspace = \App\Models\Workspace::with(['members', 'boards.lists'])
                ->findOrFail($validated['workspace_id']);
            
            $hasWorkspaceAccess = $workspace->members->contains('id', $creator->id) || 
                                 $workspace->creator_id === $creator->id;

            if (!$hasWorkspaceAccess) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['User does not have access to this workspace']
                ]);
            }

            // Find board by title in workspace
            $board = $workspace->boards()
                              ->where('title', $validated['board_title'])
                              ->with('lists')
                              ->first();

            if (!$board) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['Board not found with title: ' . $validated['board_title']],
                    'available_boards' => $workspace->boards->pluck('title')
                ]);
            }

            // Check board access
            $hasBoardAccess = $board->members->contains('id', $creator->id) || 
                            $board->creator_id === $creator->id;

            if (!$hasBoardAccess) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['User does not have access to this board']
                ]);
            }

            // Find list by title in board
            $list = $board->lists()
                         ->where('title', $validated['list_title'])
                         ->first();

            if (!$list) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['List not found with title: ' . $validated['list_title']],
                    'available_lists' => $board->lists->pluck('title')
                ]);
            }

            return response()->json([
                'valid' => true,
                'data' => [
                    'workspace_id' => $workspace->id,
                    'workspace_name' => $workspace->name,
                    'board_id' => $board->id,
                    'board_title' => $board->title,
                    'list_id' => $list->id,
                    'list_title' => $list->title,
                    'creator_id' => $creator->id,
                    'creator_name' => $creator->name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }
}