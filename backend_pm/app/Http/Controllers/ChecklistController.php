<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Checklist;

class ChecklistController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'card_id' => 'required|exists:cards,id',
            'title' => 'required|string|max:255',
        ]);

        $card = Card::with('list.board')->find($data['card_id']);

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

        $maxPosition = Checklist::where('card_id', $data['card_id'])->max('position') ?? -1;

        $data['position'] = $maxPosition + 1;

        $checklist = Checklist::create($data);

        return response()->json([
            'checklist' => $checklist->load('items')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $checklist = Checklist::with('card.list.board')->find($id);

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
            'title' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|integer|min:0',
        ]);

        $checklist->update($data);

        return response()->json([
            'checklist' => $checklist->fresh()
        ], 200);
    }

    public function destroy($id)
    {
        $checklist = Checklist::with('card.list.board')->find($id);

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

        $checklist->delete();

        return response()->json([
            'message' => 'Checklist deleted'
        ], 200);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'checklists' => 'required|array',
            'checklists.*.id' => 'required|exists:checklists,id',
            'checklists.*.position' => 'required|integer|min:0',
        ]);

        foreach ($data['checklists'] as $checklistData) {
            Checklist::where('id', $checklistData['id'])->update([
                'position' => $checklistData['position']
            ]);
        }

        return response()->json([
            'message' => 'Checklists reordered successfully'
        ], 200);
    }

    public function show($id)
    {
        $checklist = Checklist::with(['card.list.board', 'items'])
            ->find($id);

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

        $checklist->progress = $checklist->getProgressAttribute();

        return response()->json([
            'checklist' => $checklist
        ], 200);
    }

    public function duplicate($id)
    {
        $originalChecklist = Checklist::with(['card.list.board', 'items'])
            ->find($id);

        if (!$originalChecklist) {
            return response()->json([
                'message' => 'Checklist not found'
            ], 404);
        }

        if (!$originalChecklist->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $maxPosition = Checklist::where('card_id', $originalChecklist->card_id)
            ->max('position') ?? -1;

        $newChecklist = Checklist::create([
            'card_id' => $originalChecklist->card_id,
            'title' => $originalChecklist->title . ' (Copy)',
            'position' => $maxPosition + 1,
        ]);

        foreach ($originalChecklist->items as $item) {
            $newChecklist->items()->create([
                'title' => $item->title,
                'position' => $item->position,
                'completed' => false,
            ]);
        }

        return response()->json([
            'checklist' => $newChecklist->load('items')
        ], 201);
    }
}