<?php

use App\Http\Controllers\AiBuilderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuilderController;
use App\Http\Controllers\ChatApiController;
use App\Http\Controllers\ChatsController;
use App\Http\Controllers\ContactApiController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => inertia('Landing'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::middleware(['auth'])->prefix('app')->group(function () {
    Route::get('/dashboard', DashboardController::class);
    Route::get('/builder', [BuilderController::class, 'show']);
    Route::post('/builder/save', [BuilderController::class, 'save']);
    Route::post('/builder/reorder', [BuilderController::class, 'reorder']);
    Route::post('/builder/template', [BuilderController::class, 'applyTemplate']);
    Route::post('/builder/publish', [BuilderController::class, 'publish']);

    Route::post('/ai/generate', [AiBuilderController::class, 'generate']);
    Route::post('/ai/refine', [AiBuilderController::class, 'refine']);
    Route::post('/ai/fill-from-profile', [AiBuilderController::class, 'fillFromProfile']);

    Route::get('/content', [ContentController::class, 'show']);
    Route::put('/content', [ContentController::class, 'update']);

    Route::get('/knowledge', [KnowledgeController::class, 'show']);
    Route::post('/knowledge/upload', [KnowledgeController::class, 'upload']);
    Route::post('/knowledge/url', [KnowledgeController::class, 'addUrl']);
    Route::post('/knowledge/text', [KnowledgeController::class, 'addText']);
    Route::post('/knowledge/{document}/reprocess', [KnowledgeController::class, 'reprocess']);
    Route::delete('/knowledge/{source}', [KnowledgeController::class, 'destroy']);

    Route::get('/domains', [DomainController::class, 'show']);
    Route::post('/domains', [DomainController::class, 'store']);
    Route::post('/domains/{domain}/verify', [DomainController::class, 'verify']);
    Route::post('/domains/{domain}/primary', [DomainController::class, 'makePrimary']);
    Route::delete('/domains/{domain}', [DomainController::class, 'destroy']);

    Route::get('/chats', [ChatsController::class, 'index']);
    Route::get('/leads', [LeadsController::class, 'index']);
    Route::post('/leads/{lead}/status', [LeadsController::class, 'updateStatus']);
});

Route::post('/api/chat/{slug}', [ChatApiController::class, 'stream']);
Route::post('/api/contact/{slug}', [ContactApiController::class, 'store']);

Route::get('/site/{slug?}', [PublicSiteController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('site.show');
