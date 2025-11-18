<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Checklist;
use App\Models\ChecklistItem;

class ChecklistItemController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'checklist_id' => 'required|exists:checklists,id',
            'text' => 'required|string',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $checklist = Checklist::with('card.list.board')->find($data['checklist_id']);

        if (!$checklist) {
            return response()->json([
                'message' => 'Checklist not found'
            ], 404);
        }

        if (!$checklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $maxPosition = ChecklistItem::where('checklist_id', $data['checklist_id'])
            ->max('position') ?? -1;

        $data['position'] = $maxPosition + 1;

        $item = ChecklistItem::create($data);

        return response()->json([
            'item' => $item->load('assignee')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = ChecklistItem::with('checklist.card.list.board')->find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Checklist item not found'
            ], 404);
        }

        if (!$item->checklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'text' => 'sometimes|required|string',
            'completed' => 'sometimes|boolean',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'position' => 'sometimes|integer|min:0',
        ]);

        $item->update($data);

        return response()->json([
            'message' => 'Checklist item updated',
            'item' => $item->load('assignee')
        ], 200);
    }

    public function destroy($id)
    {
        $item = ChecklistItem::with('checklist.card.list.board')->find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Checklist item not found'
            ], 404);
        }

        if (!$item->checklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $item->delete();

        return response()->json([
            'message' => 'Checklist item deleted'
        ], 200);
    }

    public function toggleComplete($id)
    {
        $item = ChecklistItem::with('checklist.card.list.board')->find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Checklist item not found'
            ], 404);
        }

        if (!$item->checklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $item->update([
            'completed' => !$item->completed
        ]);

        return response()->json([
            'message' => 'Checklist item completed',
            'item' => $item->load('assignee')
        ], 200);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:checklist_items,id',
            'items.*.position' => 'required|integer|min:0',
        ]);

        foreach ($data['items'] as $itemData) {
            ChecklistItem::where('id', $itemData['id'])->update([
                'position' => $itemData['position']
            ]);
        }

        return response()->json([
            'message' => 'Checklist items reordered successfully'
        ], 200);
    }

    public function bulkUpdate(Request $request, $checklistId)
    {
        $checklist = Checklist::with('card.list.board')->find($checklistId);

        if (!$checklist) {
            return response()->json([
                'message' => 'Checklist not found'
            ], 404);
        }

        if (!$checklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'sometimes|exists:checklist_items,id',
            'items.*.text' => 'sometimes|required|string',
            'items.*.completed' => 'sometimes|boolean',
            'items.*.due_date' => 'nullable|date',
            'items.*.assigned_to' => 'nullable|exists:users,id',
            'items.*.position' => 'sometimes|integer|min:0',
            'items.*.action' => 'sometimes|in:create,update,delete',
        ]);

        $results = [];

        foreach ($data['items'] as $itemData) {
            if (isset($itemData['action']) && $itemData['action'] === 'delete' && isset($itemData['id'])) {
                $item = ChecklistItem::where('id', $itemData['id'])->where('checklist_id', $checklistId)->first();
                if ($item) {
                    $item->delete();
                    $results[] = ['id' => $itemData['id'], 'action' => 'deleted'];
                }
            } elseif (isset($itemData['id'])) {
                $item = ChecklistItem::where('id', $itemData['id'])->where('checklist_id', $checklistId)->first();
                if ($item) {
                    $updateData = array_filter([
                        'text' => $itemData['text'] ?? null,
                        'completed' => $itemData['completed'] ?? null,
                        'due_date' => $itemData['due_date'] ?? null,
                        'assigned_to' => $itemData['assigned_to'] ?? null,
                        'position' => $itemData['position'] ?? null,
                    ], fn($value) => !is_null($value));
                    
                    $item->update($updateData);
                    $results[] = ['id' => $itemData['id'], 'action' => 'updated', 'item' => $item];
                }
            } else {
                $maxPosition = ChecklistItem::where('checklist_id', $checklistId)->max('position') ?? -1;
                
                $item = ChecklistItem::create([
                    'checklist_id' => $checklistId,
                    'text' => $itemData['text'],
                    'due_date' => $itemData['due_date'] ?? null,
                    'assigned_to' => $itemData['assigned_to'] ?? null,
                    'position' => $maxPosition + 1,
                ]);
                $results[] = ['id' => $item->id, 'action' => 'created', 'item' => $item];
            }
        }

        return response()->json([
            'message' => 'Checklist items updated successfully',
            'results' => $results
        ], 200);
    }

    public function assign(Request $request, $id)
    {
        $item = ChecklistItem::with('checklist.card.list.board')->find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Checklist item not found'
            ], 404);
        }

        if (!$item->checklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $item->update(['assigned_to' => $data['assigned_to']]);

        return response()->json([
            'message' => 'Checklist item assigned successfully',
            'item' => $item->load('assignee')
        ], 200);
    }

    public function unassign($id)
    {
        $item = ChecklistItem::with('checklist.card.list.board')->find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Checklist item not found'
            ], 404);
        }

        if (!$item->checklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $item->update(['assigned_to' => null]);

        return response()->json([
            'message' => 'Checklist item unassigned successfully',
            'item' => $item->load('assignee')
        ], 200);
    }
}