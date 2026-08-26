<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ParentPortal\ParentForumController;
use App\Http\Controllers\Api\ParentPortal\ParentPortalController;
use App\Http\Controllers\Api\ParentPortal\SchoolParentForumController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('me', [ParentPortalController::class, 'me'])->name('me');
        Route::get('children', [ParentPortalController::class, 'children'])->name('children');
        Route::get('children/{studentId}', [ParentPortalController::class, 'child'])
            ->whereNumber('studentId')
            ->name('children.show');
        Route::get('children/{studentId}/activities', [ParentPortalController::class, 'activities'])
            ->whereNumber('studentId')
            ->name('children.activities');
        Route::get('children/{studentId}/presences', [ParentPortalController::class, 'presences'])
            ->whereNumber('studentId')
            ->name('children.presences');
        Route::get('children/{studentId}/bulletin', [ParentPortalController::class, 'bulletin'])
            ->whereNumber('studentId')
            ->name('children.bulletin');

        Route::get('forum/threads', [ParentForumController::class, 'index'])->name('forum.threads.index');
        Route::post('forum/threads', [ParentForumController::class, 'store'])->name('forum.threads.store');
        Route::get('forum/threads/{threadId}', [ParentForumController::class, 'show'])
            ->whereNumber('threadId')
            ->name('forum.threads.show');
        Route::post('forum/threads/{threadId}/messages', [ParentForumController::class, 'reply'])
            ->whereNumber('threadId')
            ->name('forum.threads.reply');
    });

Route::middleware(['auth:sanctum', 'permission:parent.forum.manage'])
    ->prefix('school/parent-forum')
    ->name('school.parent-forum.')
    ->group(function () {
        Route::get('threads', [SchoolParentForumController::class, 'index'])->name('threads.index');
        Route::get('threads/{threadId}', [SchoolParentForumController::class, 'show'])
            ->whereNumber('threadId')
            ->name('threads.show');
        Route::post('threads/{threadId}/messages', [SchoolParentForumController::class, 'reply'])
            ->whereNumber('threadId')
            ->name('threads.reply');
        Route::post('threads/{threadId}/close', [SchoolParentForumController::class, 'close'])
            ->whereNumber('threadId')
            ->name('threads.close');
        Route::post('threads/{threadId}/reopen', [SchoolParentForumController::class, 'reopen'])
            ->whereNumber('threadId')
            ->name('threads.reopen');
    });
