<?php

use App\Http\Controllers\DuelController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

// ── Duel Routes ───────────────────────────────────────────────────────────────
Route::get('/duels',                      [DuelController::class, 'index'])->name('duels.index');
Route::post('/duels/{scenario}/run',      [DuelController::class, 'run'])->name('duels.run');
Route::post('/duels/{scenario}/compare',  [DuelController::class, 'compare'])->name('duels.compare');
Route::get('/duels/{duel}/status',        [DuelController::class, 'status'])->name('duels.status');
Route::get('/duels/{duel}/live',          [DuelController::class, 'live'])->name('duels.live');
Route::get('/duels/{duel}/report',        [DuelController::class, 'report'])->name('duels.report');
Route::get('/duels/{duel}/report/html',   [ReportController::class, 'html'])->name('duels.report.html');
Route::get('/duels/history/all',          [DuelController::class, 'history'])->name('duels.history');

// ── Scenario Routes ───────────────────────────────────────────────────────────
Route::post('/scenarios',                 [ScenarioController::class, 'store'])->name('scenarios.store');
Route::delete('/scenarios/{scenario}',    [ScenarioController::class, 'destroy'])->name('scenarios.destroy');

// ── API Routes ────────────────────────────────────────────────────────────────
Route::get('/api/stats',                  [DuelController::class, 'stats'])->name('api.stats');
Route::get('/api/health',                 [HealthController::class, 'check'])->name('api.health');

// ── Demo Routes ───────────────────────────────────────────────────────────────
Route::post('/demo/seed',                 [DemoController::class, 'seed'])->name('demo.seed');
Route::post('/demo/reset',                [DemoController::class, 'reset'])->name('demo.reset');
