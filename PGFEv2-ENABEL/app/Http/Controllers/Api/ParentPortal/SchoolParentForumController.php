<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\ParentForumMessage;
use App\Models\ParentForumThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Côté école : répondre aux questions des parents.
 */
final class SchoolParentForumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = ParentForumThread::query()
            ->with([
                'parent:id,name,firstname,lastname,email,phone_number',
                'student:id,name,firstname,lastname',
                'messages' => fn ($q) => $q->latest('id')->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.mb_strtolower((string) $request->input('search')).'%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(subject) LIKE ?', [$search])
                    ->orWhereHas('parent', function ($p) use ($search) {
                        $p->whereRaw('LOWER(name) LIKE ?', [$search])
                            ->orWhereRaw('LOWER(firstname) LIKE ?', [$search])
                            ->orWhereRaw('LOWER(lastname) LIKE ?', [$search]);
                    });
            });
        }

        $threads = $query->limit(100)->get()->map(function (ParentForumThread $t) {
            $last = $t->messages->first();

            return [
                'id' => $t->id,
                'subject' => $t->subject,
                'status' => $t->status,
                'parent' => $t->parent ? [
                    'id' => $t->parent->id,
                    'name' => trim($t->parent->name.' '.$t->parent->firstname.' '.$t->parent->lastname),
                    'email' => $t->parent->email,
                    'phone_number' => $t->parent->phone_number,
                ] : null,
                'student' => $t->student ? [
                    'id' => $t->student->id,
                    'name' => trim($t->student->name.' '.$t->student->firstname.' '.$t->student->lastname),
                ] : null,
                'last_message' => $last ? [
                    'body' => $last->body,
                    'author_role' => $last->author_role,
                    'created_at' => optional($last->created_at)?->toIso8601String(),
                ] : null,
                'last_message_at' => optional($t->last_message_at)?->toIso8601String(),
                'created_at' => optional($t->created_at)?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $threads,
            'count' => $threads->count(),
        ]);
    }

    public function show(Request $request, int $threadId): JsonResponse
    {
        $thread = $this->findSchoolThread($request, $threadId);
        if ($thread instanceof JsonResponse) {
            return $thread;
        }

        $thread->load([
            'parent:id,name,firstname,lastname,email,phone_number',
            'student:id,name,firstname,lastname',
            'messages.user:id,name,email',
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->serializeThread($thread),
        ]);
    }

    public function reply(Request $request, int $threadId): JsonResponse
    {
        $thread = $this->findSchoolThread($request, $threadId);
        if ($thread instanceof JsonResponse) {
            return $thread;
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        if ($thread->status === 'closed') {
            return response()->json([
                'status' => false,
                'message' => 'Cette discussion est clôturée.',
            ], 422);
        }

        $message = ParentForumMessage::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'author_role' => 'staff',
            'body' => $data['body'],
        ]);

        $thread->last_message_at = now();
        $thread->save();
        $message->load('user:id,name,email');

        return response()->json([
            'status' => true,
            'message' => 'Réponse envoyée.',
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'author_role' => $message->author_role,
                'author_name' => $message->user?->name ?? 'École',
                'created_at' => optional($message->created_at)?->toIso8601String(),
            ],
        ], 201);
    }

    public function close(Request $request, int $threadId): JsonResponse
    {
        $thread = $this->findSchoolThread($request, $threadId);
        if ($thread instanceof JsonResponse) {
            return $thread;
        }

        $thread->status = 'closed';
        $thread->save();

        return response()->json([
            'status' => true,
            'message' => 'Discussion clôturée.',
            'data' => ['id' => $thread->id, 'status' => $thread->status],
        ]);
    }

    public function reopen(Request $request, int $threadId): JsonResponse
    {
        $thread = $this->findSchoolThread($request, $threadId);
        if ($thread instanceof JsonResponse) {
            return $thread;
        }

        $thread->status = 'open';
        $thread->save();

        return response()->json([
            'status' => true,
            'message' => 'Discussion rouverte.',
            'data' => ['id' => $thread->id, 'status' => $thread->status],
        ]);
    }

    private function findSchoolThread(Request $request, int $threadId): ParentForumThread|JsonResponse
    {
        $user = $request->user();
        $query = ParentForumThread::query()->where('id', $threadId);
        if ($user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $thread = $query->first();
        if (! $thread) {
            return response()->json(['status' => false, 'message' => 'Discussion introuvable.'], 404);
        }

        return $thread;
    }

    private function serializeThread(ParentForumThread $thread): array
    {
        return [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'status' => $thread->status,
            'parent' => $thread->parent ? [
                'id' => $thread->parent->id,
                'name' => trim($thread->parent->name.' '.$thread->parent->firstname.' '.$thread->parent->lastname),
                'email' => $thread->parent->email,
                'phone_number' => $thread->parent->phone_number,
            ] : null,
            'student' => $thread->student ? [
                'id' => $thread->student->id,
                'name' => trim($thread->student->name.' '.$thread->student->firstname.' '.$thread->student->lastname),
            ] : null,
            'messages' => $thread->messages->sortBy('id')->values()->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'author_role' => $m->author_role,
                'author_name' => $m->user?->name ?? ($m->author_role === 'staff' ? 'École' : 'Parent'),
                'created_at' => optional($m->created_at)?->toIso8601String(),
            ]),
            'last_message_at' => optional($thread->last_message_at)?->toIso8601String(),
            'created_at' => optional($thread->created_at)?->toIso8601String(),
        ];
    }
}
