<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Comment;

class CommentController extends Controller
{
    public function index($cardId)
    {
        $card = Card::with('list.board')->find($cardId);

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
            'comments' => $card->comments()->with('user')->get()
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'card_id' => 'required|exists:cards,id',
            'text' => 'required|string',
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

        $data['user_id'] = auth()->id();

        $comment = Comment::create($data);

        return response()->json([
            'comment' => $comment->load('user')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
        }

        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'text' => 'required|string',
        ]);

        $comment->update($data);

        return response()->json([
            'comment' => $comment->load('user')
        ], 200);
    }

    public function destroy($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
        }

        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $comment->delete();

        return response()->json(
            ['message' => 'Comment deleted'
        ], 200);
    }

    public function show($id)
    {
        $comment = Comment::with(['card.list.board', 'user'])->find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
        }

        if (!$comment->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'comment' => $comment
        ], 200);
    }

    public function myRecentComments(Request $request)
    {
        $limit = $request->query('limit', 10);
        
        $comments = Comment::with(['card.list.board', 'user'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'comments' => $comments
        ], 200);
    }

    public function bulkIndex(Request $request)
    {
        $data = $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:cards,id',
        ]);

        $cards = Card::with('list.board')
            ->whereIn('id', $data['card_ids'])
            ->get();

        foreach ($cards as $card) {
            if (!$card->list->board->isMember(auth()->id())) {
                return response()->json([
                    'message' => 'Unauthorized access to one or more cards'
                ], 403);
            }
        }

        $comments = Comment::with(['card', 'user'])
            ->whereIn('card_id', $data['card_ids'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'comments' => $comments
        ], 200);
    }

    public function forceDestroy($id)
    {
        $comment = Comment::with('card.list.board')->find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
        }

        if (!$comment->card->list->board->hasRole(auth()->id(), ['owner', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted by admin'
        ], 200);
    }
}