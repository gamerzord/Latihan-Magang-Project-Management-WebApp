<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Board;
use App\Models\Card;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $boardId = $request->query('board_id');
        $cardId = $request->query('card_id');
        $limit = $request->query('limit', 50);

        $query = Activity::with(['user', 'board', 'card'])
            ->orderBy('created_at', 'desc');

        if ($boardId) {
            $board = Board::find($boardId);
            if (!$board || !$board->isMember(auth()->id())) {
                return response()->json([
                    'message' => 'Board not found or unauthorized'
                ], 404);
            }
            $query->where('board_id', $boardId);
        }

        if ($cardId) {
            $card = Card::with('list.board')->find($cardId);
            if (!$card || !$card->list->board->isMember(auth()->id())) {
                return response()->json([
                    'message' => 'Card not found or unauthorized'
                ], 404);
            }
            $query->where('card_id', $cardId);
        }

        if (!$boardId && !$cardId) {
            $userBoardIds = auth()->user()->workspaces()
                ->with('boards')
                ->get()
                ->pluck('boards')
                ->flatten()
                ->pluck('id');
            
            $query->whereIn('board_id', $userBoardIds);
        }

        $activities = $query->limit($limit)->get();

        return response()->json([
            'activities' => $activities
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'board_id' => 'nullable|exists:boards,id',
            'card_id' => 'nullable|exists:cards,id',
            'action_type' => 'required|string',
            'action_data' => 'nullable|array',
        ]);

        if (isset($data['board_id'])) {
            $board = Board::find($data['board_id']);
            if (!$board || !$board->isMember(auth()->id())) {
                return response()->json([
                    'message' => 'Unauthorized access to board'
                ], 403);
            }
        }

        if (isset($data['card_id'])) {
            $card = Card::with('list.board')->find($data['card_id']);
            if (!$card || !$card->list->board->isMember(auth()->id())) {
                return response()->json([
                    'message' => 'Unauthorized access to card'
                ], 403);
            }
        }

        $data['user_id'] = auth()->id();

        $activity = Activity::create($data);

        return response()->json([
            'activity' => $activity->load(['user', 'board', 'card'])
        ], 201);
    }

    public function myActivity(Request $request)
    {
        $limit = $request->query('limit', 30);
        
        $userBoardIds = auth()->user()->workspaces()
            ->with('boards')
            ->get()
            ->pluck('boards')
            ->flatten()
            ->pluck('id');

        $activities = Activity::with(['user', 'board', 'card'])
            ->whereIn('board_id', $userBoardIds)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'activities' => $activities
        ], 200);
    }

    public function boardActivity($boardId, Request $request)
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

        $perPage = $request->query('per_page', 25);
        $page = $request->query('page', 1);

        $activities = Activity::with(['user', 'card'])
            ->where('board_id', $boardId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'activities' => $activities
        ], 200);
    }

    public function cardActivity($cardId)
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

        $activities = Activity::with(['user'])
            ->where('card_id', $cardId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'activities' => $activities
        ], 200);
    }

    public function stats(Request $request)
    {
        $boardId = $request->query('board_id');
        $days = $request->query('days', 30);

        $query = Activity::query();

        if ($boardId) {
            $board = Board::find($boardId);
            if (!$board || !$board->isMember(auth()->id())) {
                return response()->json([
                    'message' => 'Board not found or unauthorized'
                ], 404);
            }
            $query->where('board_id', $boardId);
        } else {
            $userBoardIds = auth()->user()->workspaces()
                ->with('boards')
                ->get()
                ->pluck('boards')
                ->flatten()
                ->pluck('id');
            
            $query->whereIn('board_id', $userBoardIds);
        }

        $startDate = now()->subDays($days);

        $stats = [
            'total_activities' => $query->where('created_at', '>=', $startDate)->count(),
            'activities_by_type' => $query->where('created_at', '>=', $startDate)
                ->selectRaw('action_type, count(*) as count')
                ->groupBy('action_type')
                ->get()
                ->pluck('count', 'action_type'),
            'activities_by_day' => $query->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get(),
            'top_users' => $query->where('created_at', '>=', $startDate)
                ->with('user')
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'user' => $item->user,
                        'activity_count' => $item->count
                    ];
                }),
        ];

        return response()->json([
            'stats' => $stats
        ], 200);
    }

    public function clearOldActivities(Request $request)
    {
        $data = $request->validate([
            'days_old' => 'required|integer|min:1',
        ]);

        $userBoardIds = auth()->user()->workspaces()
            ->wherePivotIn('role', ['owner', 'admin'])
            ->with('boards')
            ->get()
            ->pluck('boards')
            ->flatten()
            ->pluck('id');

        $cutoffDate = now()->subDays($data['days_old']);

        $deletedCount = Activity::whereIn('board_id', $userBoardIds)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        return response()->json([
            'message' => "{$deletedCount} activities older than {$data['days_old']} days deleted"
        ]);
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'query' => 'required|string|min:2',
            'board_id' => 'nullable|exists:boards,id',
        ]);

        $userBoardIds = auth()->user()->workspaces()
            ->with('boards')
            ->get()
            ->pluck('boards')
            ->flatten()
            ->pluck('id');

        $query = Activity::with(['user', 'board', 'card'])
            ->whereIn('board_id', $userBoardIds)
            ->where(function($q) use ($data) {
                $q->where('action_type', 'LIKE', "%{$data['query']}%")
                  ->orWhereHas('user', function($userQuery) use ($data) {
                      $userQuery->where('name', 'LIKE', "%{$data['query']}%");
                  });
            });

        if (isset($data['board_id'])) {
            $board = Board::find($data['board_id']);
            if ($board && $board->isMember(auth()->id())) {
                $query->where('board_id', $data['board_id']);
            }
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'activities' => $activities
        ], 200);
    }
}