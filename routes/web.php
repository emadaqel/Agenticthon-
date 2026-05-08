<?php

use App\Http\Controllers\DuelController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

// ── Duel Routes ───────────────────────────────────────────────────────────────
Route::get('/duels',                    [DuelController::class, 'index'])->name('duels.index');
Route::post('/duels/{scenario}/run',   [DuelController::class, 'run'])->name('duels.run');
Route::get('/duels/{duel}/status',     [DuelController::class, 'status'])->name('duels.status');
Route::get('/duels/{duel}/report',     [DuelController::class, 'report'])->name('duels.report');
Route::get('/duels/history/all',       [DuelController::class, 'history'])->name('duels.history');

// ── API Routes ────────────────────────────────────────────────────────────────
Route::get('/api/stats',               [DuelController::class, 'stats'])->name('api.stats');
