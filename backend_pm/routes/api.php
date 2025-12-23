<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\UserController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('api.key')->group(function () {
    Route::post('/cards/from-email', [CardController::class, 'createFromEmail']);
    Route::post('/cards/from-email/validate', [CardController::class, 'validateEmailData']);
});


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/users/search', [UserController::class, 'search']); 
    
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('workspaces/{id}/members', [WorkspaceController::class, 'addMember']);
    Route::delete('workspaces/{id}/members/{userId}', [WorkspaceController::class, 'removeMember']);
    Route::patch('workspaces/{id}/members/{userId}/role', [WorkspaceController::class, 'updateMemberRole']);
    Route::post('workspaces/{id}/leave', [WorkspaceController::class, 'leave']);
    Route::get('workspaces/{id}/membership', [WorkspaceController::class, 'myMembership']);

    Route::get('workspaces/{workspaceId}/available-members', [WorkspaceController::class, 'availableMembers']); 

    Route::apiResource('boards', BoardController::class);
    Route::post('boards/{id}/members', [BoardController::class, 'addMember']);
    Route::delete('boards/{id}/members/{userId}', [BoardController::class, 'removeMember']);
    Route::post('boards/{id}/leave', [BoardController::class, 'leave']);
    Route::patch('boards/{id}/members/{userId}/role', [BoardController::class, 'updateMemberRole']);

    Route::get('boards/{boardId}/available-members', [BoardController::class, 'availableMembers']); 

    Route::apiResource('lists', ListController::class)->except(['index', 'show']);
    Route::post('lists/reorder', [ListController::class, 'reorder']);
    Route::post('lists/{id}/archive', [ListController::class, 'archive']);
    Route::post('lists/{id}/restore', [ListController::class, 'restore']);

    Route::apiResource('cards', CardController::class)->except(['index']);
    Route::post('cards/{id}/move', [CardController::class, 'move']);
    Route::post('cards/{id}/labels', [CardController::class, 'addLabel']);
    Route::delete('cards/{id}/labels/{labelId}', [CardController::class, 'removeLabel']);
    Route::post('cards/{id}/members', [CardController::class, 'addMember']);
    Route::delete('cards/{id}/members/{userId}', [CardController::class, 'removeMember']);
    Route::patch('cards/{id}/members/{userId}/role', [CardController::class, 'updateMemberRole']);
    Route::post('cards/{id}/archive', [CardController::class, 'archive']);
    Route::post('cards/{id}/restore', [CardController::class, 'restore']);
    Route::post('cards/{id}/toggle-due', [CardController::class, 'toggleDueDateCompletion']);

    Route::get('cards/{cardId}/available-members', [CardController::class, 'availableMembers']);

    Route::get('boards/{boardId}/labels', [LabelController::class, 'index']);
    Route::apiResource('labels', LabelController::class)->except(['index', 'show']);
    Route::post('boards/{boardId}/labels/bulk', [LabelController::class, 'bulkUpdate']);
    Route::get('boards/{boardId}/labels/usage', [LabelController::class, 'usage']);

    Route::apiResource('checklists', ChecklistController::class)->except(['index', 'show']);
    Route::post('checklists/reorder', [ChecklistController::class, 'reorder']);
    Route::get('checklists/{id}', [ChecklistController::class, 'show']);
    Route::post('checklists/{id}/duplicate', [ChecklistController::class, 'duplicate']);

    Route::apiResource('checklist-items', ChecklistItemController::class)->except(['index', 'show']);
    Route::post('checklist-items/reorder', [ChecklistItemController::class, 'reorder']);
    Route::post('checklists/{checklistId}/items/bulk', [ChecklistItemController::class, 'bulkUpdate']);
    Route::post('checklist-items/{id}/toggle', [ChecklistItemController::class, 'toggleComplete']);
    Route::post('checklist-items/{id}/assign', [ChecklistItemController::class, 'assign']);
    Route::post('checklist-items/{id}/unassign', [ChecklistItemController::class, 'unassign']);

    Route::get('cards/{cardId}/comments', [CommentController::class, 'index']);
    Route::apiResource('comments', CommentController::class)->except(['index', 'show']);
    Route::get('comments/my-recent', [CommentController::class, 'myRecentComments']);
    Route::post('comments/bulk', [CommentController::class, 'bulkIndex']);
    Route::delete('comments/{id}/force', [CommentController::class, 'forceDestroy']);

    Route::apiResource('attachments', AttachmentController::class)->only(['store', 'destroy']);
    Route::get('cards/{cardId}/attachments', [AttachmentController::class, 'index']);
    Route::get('attachments/{id}', [AttachmentController::class, 'show']);
    Route::patch('attachments/{id}', [AttachmentController::class, 'update']);
    Route::get('attachments/{id}/download', [AttachmentController::class, 'download']);
    Route::delete('attachments/bulk', [AttachmentController::class, 'bulkDestroy']);
    Route::get('cards/{cardId}/attachments/stats', [AttachmentController::class, 'stats']);

    Route::apiResource('activities', ActivityController::class)->only(['index', 'store']);
    Route::get('activities/my-activity', [ActivityController::class, 'myActivity']);
    Route::get('boards/{boardId}/activities', [ActivityController::class, 'boardActivity']);
    Route::get('cards/{cardId}/activities', [ActivityController::class, 'cardActivity']);
    Route::get('activities/stats', [ActivityController::class, 'stats']);
    Route::delete('activities/clear-old', [ActivityController::class, 'clearOldActivities']);
    Route::get('activities/search', [ActivityController::class, 'search']);
});