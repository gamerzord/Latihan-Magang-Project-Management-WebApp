<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\BoardList;

class ListController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'board_id' => 'required|exists:boards,id',
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
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

        $maxPosition = BoardList::where('board_id', $data['board_id'])->max('position') ?? -1;

        $data['position'] = $maxPosition + 1;
        $data['created_by'] = auth()->id();

        $list = BoardList::create($data);

        return response()->json([
            'list' => $list->load('cards')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $list = BoardList::with('board')->find($id);

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

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'color' => 'nullable|string|max:7',
            'position' => 'sometimes|required|integer|min:0',
            'archived' => 'sometimes|boolean',
        ]);

        $list->update($data);

        return response()->json([
            'list' => $list->fresh()
        ], 200);
    }

    public function destroy($id)
    {
        $list = BoardList::with('board')->find($id);

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

        $list->delete();

        return response()->json([
            'message' => 'List deleted'
        ], 200);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'lists' => 'required|array',
            'lists.*.id' => 'required|exists:lists,id',
            'lists.*.position' => 'required|integer|min:0',
        ]);

        foreach ($data['lists'] as $listData) {
            BoardList::where('id', $listData['id'])->update([
                'position' => $listData['position']
            ]);
        }

        return response()->json([
            'message' => 'Lists reordered successfully'
        ], 200);
    }

    public function archive($id)
    {
        $list = BoardList::with('board')->find($id);

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

        $list->update(['archived' => true]);

        return response()->json([
            'message' => 'List archived successfully',
            'list' => $list->fresh()
        ], 200);
    }

    public function restore($id)
    {
        $list = BoardList::with('board')->find($id);

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

        $list->update(['archived' => false]);

        return response()->json([
            'message' => 'List restored successfully',
            'list' => $list->fresh()
        ], 200);
    }
}