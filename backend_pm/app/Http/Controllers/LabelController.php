<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Label;

class LabelController extends Controller
{
    public function index($boardId)
    {
        $board = Board::find($boardId);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'labels' => $board->labels
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'board_id' => 'required|exists:boards,id',
            'name' => 'nullable|string|max:255',
            'color' => 'required|string|max:7',
        ]);

        $board = Board::find($data['board_id']);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $label = Label::create($data);

        return response()->json([
            'label' => $label
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $label = Label::with('board')->find($id);

        if (!$label) {
            return response()->json([
                'message' => 'Label not found'
            ], 404);
        }

        if (!$label->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'color' => 'sometimes|required|string|max:7',
        ]);

        $label->update($data);

        return response()->json([
            'label' => $label
        ], 200);
    }

    public function destroy($id)
    {
        $label = Label::with('board')->find($id);

        if (!$label) {
            return response()->json([
                'message' => 'Label not found'
            ], 404);
        }

        if (!$label->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $label->delete();

        return response()->json([
            'message' => 'Label deleted'
        ], 200);
    }

    public function bulkUpdate(Request $request, $boardId)
    {
        $board = Board::find($boardId);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'labels' => 'required|array',
            'labels.*.id' => 'sometimes|exists:labels,id',
            'labels.*.name' => 'nullable|string|max:255',
            'labels.*.color' => 'required|string|max:7',
            'labels.*.action' => 'sometimes|in:create,update,delete',
        ]);

        $results = [];

        foreach ($data['labels'] as $labelData) {
            if (isset($labelData['action']) && $labelData['action'] === 'delete' && isset($labelData['id'])) {
                $label = Label::where('id', $labelData['id'])->where('board_id', $boardId)->first();
                if ($label) {
                    $label->delete();
                    $results[] = ['id' => $labelData['id'], 'action' => 'deleted'];
                }
            } elseif (isset($labelData['id'])) {
                $label = Label::where('id', $labelData['id'])->where('board_id', $boardId)->first();
                if ($label) {
                    $label->update([
                        'name' => $labelData['name'] ?? null,
                        'color' => $labelData['color']
                    ]);
                    $results[] = ['id' => $labelData['id'], 'action' => 'updated', 'label' => $label];
                }
            } else {
                $label = Label::create([
                    'board_id' => $boardId,
                    'name' => $labelData['name'] ?? null,
                    'color' => $labelData['color']
                ]);
                $results[] = ['id' => $label->id, 'action' => 'created', 'label' => $label];
            }
        }

        return response()->json([
            'message' => 'Labels updated successfully',
            'results' => $results
        ], 200);
    }

    public function usage($boardId)
    {
        $board = Board::find($boardId);

        if (!$board) {
            return response()->json([
                'message' => 'Board not found'
            ], 404);
        }

        if (!$board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $labelsWithUsage = $board->labels()->withCount('cards')->get();

        return response()->json([
            'labels' => $labelsWithUsage
        ], 200);
    }
}