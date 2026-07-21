<?php

use App\Http\Controllers\DuelController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\PromptFooController;
use App\Http\Controllers\PromptCorpusController;
use App\Http\Controllers\SecurityFindingController;
use App\Http\Controllers\AttackSurfaceController;
use App\Http\Controllers\SecurityEvidenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::view('/security', 'security.index')->name('security.index');

// ── Duel Routes ───────────────────────────────────────────────────────────────
Route::get('/duels',                      [DuelController::class, 'index'])->name('duels.index');
Route::post('/duels/{scenario}/run',      [DuelController::class, 'run'])->middleware('throttle:security-mutations')->name('duels.run');
Route::post('/duels/{scenario}/compare',  [DuelController::class, 'compare'])->middleware('throttle:security-mutations')->name('duels.compare');
Route::get('/duels/{duel}/status',        [DuelController::class, 'status'])->name('duels.status');
Route::get('/duels/{duel}/live',          [DuelController::class, 'live'])->name('duels.live');
Route::get('/duels/{duel}/report',        [DuelController::class, 'report'])->name('duels.report');
Route::get('/duels/{duel}/report/html',   [ReportController::class, 'html'])->name('duels.report.html');
Route::get('/duels/history/all',          [DuelController::class, 'history'])->name('duels.history');

// ── Scenario Routes ───────────────────────────────────────────────────────────
Route::get('/scenarios',                  [ScenarioController::class, 'index'])->name('scenarios.index');
Route::post('/scenarios',                 [ScenarioController::class, 'store'])->name('scenarios.store');
Route::delete('/scenarios/{scenario}',    [ScenarioController::class, 'destroy'])->name('scenarios.destroy');

// ── API Routes ────────────────────────────────────────────────────────────────
Route::get('/api/stats',                  [DuelController::class, 'stats'])->name('api.stats');
Route::get('/api/health',                 [HealthController::class, 'check'])->name('api.health');

// ── Demo Routes ───────────────────────────────────────────────────────────────
Route::post('/demo/seed',                 [DemoController::class, 'seed'])->name('demo.seed');
Route::post('/demo/reset',                [DemoController::class, 'reset'])->name('demo.reset');

// ── PromptFoo Integration ─────────────────────────────────────────────────────
Route::get('/promptfoo',                         [PromptFooController::class, 'index'])->name('promptfoo.index');
Route::get('/promptfoo/{scenario}/export',       [PromptFooController::class, 'export'])->name('promptfoo.export');

// Versioned public prompt corpus integration
Route::get('/api/corpora',                        [PromptCorpusController::class, 'index'])->name('corpora.index');
Route::post('/api/corpora',                       [PromptCorpusController::class, 'store'])->middleware('throttle:security-mutations')->name('corpora.store');
Route::post('/api/corpora/{corpus}/sync',         [PromptCorpusController::class, 'sync'])->middleware('throttle:security-mutations')->name('corpora.sync');
Route::post('/api/corpora/{corpus}/runs',         [PromptCorpusController::class, 'run'])->middleware('throttle:security-mutations')->name('corpora.runs.store');
Route::get('/api/corpus-runs/{run}',               [PromptCorpusController::class, 'showRun'])->name('corpora.runs.show');
Route::post('/api/corpus-runs/{run}/findings',     [SecurityFindingController::class, 'materialize'])->middleware('throttle:security-mutations')->name('findings.materialize');
Route::post('/api/findings/{finding}/remediation', [SecurityFindingController::class, 'propose'])->middleware('throttle:security-mutations')->name('remediations.store');
Route::patch('/api/remediations/{proposal}/review',[SecurityFindingController::class, 'review'])->middleware('throttle:security-mutations')->name('remediations.review');
Route::get('/api/attack-surfaces',                 [AttackSurfaceController::class, 'index'])->name('attack-surfaces.index');
Route::post('/api/attack-surfaces/analyze',        [AttackSurfaceController::class, 'store'])->middleware('throttle:security-mutations')->name('attack-surfaces.store');
Route::get('/api/attack-surfaces/{assessment}',    [AttackSurfaceController::class, 'show'])->name('attack-surfaces.show');
Route::get('/api/security/demo',                    [SecurityEvidenceController::class, 'demo'])->name('security.demo');
Route::get('/api/corpus-runs/{run}/gate',           [SecurityEvidenceController::class, 'gate'])->name('security.gate');
Route::get('/api/corpus-runs/{run}/security-report',[SecurityEvidenceController::class, 'report'])->name('security.report');
