<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\ParentForumMessage;
use App\Models\ParentForumThread;
use App\Services\Parent\ParentChildrenResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ParentForumController extends Controller
{
    public function __construct(
        private readonly ParentChildrenResolver $childrenResolver,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $threads = ParentForumThread::query()
            ->withoutGlobalScopes()
            ->with(['student:id,name,firstname,lastname', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->where('parent_id', $parent->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ParentForumThread $t) => $this->serializeThread($t, includeLast: true));

        return response()->json([
            'status' => true,
            'data' => $threads,
            'count' => $threads->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
        ]);

        if (! empty($data['student_id']) && ! $this->childrenResolver->ownsChild($parent, (int) $data['student_id'])) {
            return response()->json([
                'status' => false,
                'message' => 'Cet élève ne fait pas partie de vos enfants.',
            ], 403);
        }

        $user = $request->user();
        $thread = ParentForumThread::query()->create([
            'school_id' => $parent->school_id,
            'parent_id' => $parent->id,
            'user_id' => $user->id,
            'student_id' => $data['student_id'] ?? null,
            'subject' => $data['subject'],
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        ParentForumMessage::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'author_role' => 'parent',
            'body' => $data['body'],
        ]);

        $thread->load(['student:id,name,firstname,lastname', 'messages.user:id,name,email']);

        return response()->json([
            'status' => true,
            'message' => 'Question publiée.',
            'data' => $this->serializeThread($thread, includeMessages: true),
        ], 201);
    }

    public function show(Request $request, int $threadId): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $thread = ParentForumThread::query()
            ->withoutGlobalScopes()
            ->with(['student:id,name,firstname,lastname', 'messages.user:id,name,email'])
            ->where('parent_id', $parent->id)
            ->where('id', $threadId)
            ->first();

        if (! $thread) {
            return response()->json(['status' => false, 'message' => 'Discussion introuvable.'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->serializeThread($thread, includeMessages: true),
        ]);
    }

    public function reply(Request $request, int $threadId): JsonResponse
    {
        $parent = $this->requireParent();
        if ($parent instanceof JsonResponse) {
            return $parent;
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $thread = ParentForumThread::query()
            ->withoutGlobalScopes()
            ->where('parent_id', $parent->id)
            ->where('id', $threadId)
            ->first();

        if (! $thread) {
            return response()->json(['status' => false, 'message' => 'Discussion introuvable.'], 404);
        }

        if ($thread->status === 'closed') {
            return response()->json([
                'status' => false,
                'message' => 'Cette discussion est clôturée.',
            ], 422);
        }

        $message = ParentForumMessage::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'author_role' => 'parent',
            'body' => $data['body'],
        ]);

        $thread->last_message_at = now();
        $thread->save();

        $message->load('user:id,name,email');

        return response()->json([
            'status' => true,
            'message' => 'Message envoyé.',
            'data' => $this->serializeMessage($message),
        ], 201);
    }

    private function requireParent(): \App\Models\Parents|JsonResponse
    {
        $parent = $this->childrenResolver->resolveParentProfile();
        if (! $parent) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun profil parent lié à ce compte.',
            ], 403);
        }

        return $parent;
    }

    private function serializeThread(ParentForumThread $thread, bool $includeMessages = false, bool $includeLast = false): array
    {
        $payload = [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'status' => $thread->status,
            'student' => $thread->student ? [
                'id' => $thread->student->id,
                'name' => trim($thread->student->name.' '.$thread->student->firstname.' '.$thread->student->lastname),
            ] : null,
            'last_message_at' => optional($thread->last_message_at)?->toIso8601String(),
            'created_at' => optional($thread->created_at)?->toIso8601String(),
        ];

        if ($includeLast && $thread->relationLoaded('messages')) {
            $last = $thread->messages->first();
            $payload['last_message'] = $last ? $this->serializeMessage($last) : null;
        }

        if ($includeMessages) {
            $messages = $thread->relationLoaded('messages')
                ? $thread->messages->sortBy('id')->values()
                : $thread->messages()->with('user:id,name,email')->orderBy('id')->get();
            $payload['messages'] = $messages->map(fn ($m) => $this->serializeMessage($m))->values();
        }

        return $payload;
    }

    private function serializeMessage(ParentForumMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'author_role' => $message->author_role,
            'author_name' => $message->user?->name ?? 'Utilisateur',
            'created_at' => optional($message->created_at)?->toIso8601String(),
        ];
    }
}
