<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Card;
use App\Models\Attachment;

class AttachmentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'card_id' => 'required|exists:cards,id',
            'type' => 'required|in:file,link',
            'file' => 'required_if:type,file|file|max:10240',
            'url' => 'required_if:type,link|url',
            'name' => 'required_if:type,link|string|max:255',
            'display_text' => 'nullable|string|max:255',
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

        $attachmentData = [
            'card_id' => $data['card_id'],
            'type' => $data['type'],
            'uploaded_by' => auth()->id(),
            'display_text' => $data['display_text'] ?? null,
        ];

        if ($data['type'] === 'file') {
            $file = $request->file('file');
            $path = $file->store('attachments', 'public');
            
            $attachmentData['file_path'] = $path;
            $attachmentData['file_name'] = $file->getClientOriginalName();
            $attachmentData['file_url'] = Storage::url($path);
            $attachmentData['file_size'] = $file->getSize();
            $attachmentData['mime_type'] = $file->getMimeType();
        } else {
            $attachmentData['file_name'] = $data['name'];
            $attachmentData['file_url'] = $data['url'];
            $attachmentData['file_size'] = null;
            $attachmentData['mime_type'] = null;
        }

        $attachment = Attachment::create($attachmentData);

        return response()->json([
            'attachment' => $attachment->load('uploader')
        ], 201);
    }

    public function destroy($id)
    {
        $attachment = Attachment::with('card.list.board')->find($id);

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found'
            ], 404);
        }

        if (!$attachment->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($attachment->type === 'file' && $attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted'
        ]);
    }

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

        $attachments = $card->attachments()->with('uploader')->get();

        return response()->json([
            'attachments' => $attachments
        ], 200);
    }

    public function show($id)
    {
        $attachment = Attachment::with(['card.list.board', 'uploader'])->find($id);

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found'
            ], 404);
        }

        if (!$attachment->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'attachment' => $attachment
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $attachment = Attachment::with('card.list.board')->find($id);

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found'
            ], 404);
        }

        if (!$attachment->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validate([
            'display_text' => 'nullable|string|max:255',
            'file_name' => 'sometimes|string|max:255',
        ]);

        if ($attachment->type === 'link') {
            $updateData = array_filter([
                'display_text' => $data['display_text'] ?? null,
                'file_name' => $data['file_name'] ?? null,
            ], fn($value) => !is_null($value));
            
            $attachment->update($updateData);
        } else {
            if (isset($data['display_text'])) {
                $attachment->update(['display_text' => $data['display_text']]);
            }
        }

        return response()->json([
            'attachment' => $attachment->load('uploader')
        ], 200);
    }

    public function download($id)
    {
        $attachment = Attachment::with('card.list.board')->find($id);

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found'
            ], 404);
        }

        if (!$attachment->card->list->board->isMember(auth()->id())) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($attachment->type !== 'file') {
            return response()->json([
                'message' => 'This attachment is not a file'
            ], 400);
        }

        if (!$attachment->file_path || !Storage::exists($attachment->file_path)) {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }

        return Storage::download($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'attachment_ids' => 'required|array',
            'attachment_ids.*' => 'exists:attachments,id',
        ]);

        $attachments = Attachment::with('card.list.board')
            ->whereIn('id', $data['attachment_ids'])
            ->get();

        foreach ($attachments as $attachment) {
            if (!$attachment->card->list->board->isMember(auth()->id())) {
                return response()->json([
                    'message' => 'Unauthorized access to one or more attachments'
                ], 403);
            }
        }

        $deletedCount = 0;
        foreach ($attachments as $attachment) {
            if ($attachment->type === 'file' && $attachment->file_path) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            
            $attachment->delete();
            $deletedCount++;
        }

        return response()->json([
            'message' => "{$deletedCount} attachments deleted successfully"
        ]);
    }

    public function stats($cardId)
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

        $stats = [
            'total' => $card->attachments()->count(),
            'files' => $card->attachments()->where('type', 'file')->count(),
            'links' => $card->attachments()->where('type', 'link')->count(),
            'total_size' => $card->attachments()->where('type', 'file')->sum('file_size'),
        ];

        return response()->json([
            'stats' => $stats
        ], 200);
    }
}