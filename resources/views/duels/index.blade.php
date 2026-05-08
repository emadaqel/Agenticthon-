<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red-Team Arena Orchestrator</title>
    <meta name="description" content="Autonomous multi-agent adversarial simulation system for LLM safety testing.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --red:    #ef4444;
            --orange: #f97316;
            --green:  #22c55e;
            --yellow: #eab308;
            --blue:   #3b82f6;
            --bg:     #080c14;
            --panel:  #0e1622;
            --border: rgba(255,255,255,0.08);
            --text:   #e2e8f0;
            --muted:  #64748b;
            --mono:   'JetBrains Mono', monospace;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 15px; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; }

        /* ── Header ─────────────────────────────────────────────────────── */
        .header {
            background: linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(249,115,22,0.06) 100%);
            border-bottom: 1px solid var(--border);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-logo {
            display: flex; align-items: center; gap: .75rem;
        }
        .logo-icon {
            width: 2.5rem; height: 2.5rem; border-radius: .5rem;
            background: linear-gradient(135deg, var(--red), var(--orange));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 800; color: #fff;
        }
        .header h1 { font-size: 1.4rem; font-weight: 800; letter-spacing: -.5px; }
        .header h1 span { color: var(--orange); }
        .header-subtitle { font-size: .75rem; color: var(--muted); }

        /* ── Layout ─────────────────────────────────────────────────────── */
        .layout { display: grid; grid-template-columns: 340px 1fr; height: calc(100vh - 73px); overflow: hidden; }

        /* ── Sidebar (Scenario List) ─────────────────────────────────────── */
        .sidebar {
            border-right: 1px solid var(--border);
            overflow-y: auto;
            background: var(--panel);
            display: flex; flex-direction: column;
        }
        .sidebar-title {
            padding: 1rem 1.2rem .75rem;
            font-size: .65rem; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: var(--muted);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; background: var(--panel); z-index: 1;
        }
        .scenario-card {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background .15s, border-left .15s;
            border-left: 3px solid transparent;
        }
        .scenario-card:hover { background: rgba(255,255,255,0.03); }
        .scenario-card.active { border-left-color: var(--red); background: rgba(239,68,68,0.07); }

        .sc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .4rem; }
        .badge-cat {
            font-size: .65rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; padding: .15rem .55rem; border-radius: .25rem;
        }
        .cat-jailbreak         { background: rgba(239,68,68,.2);  color: #f87171; }
        .cat-self_harm         { background: rgba(239,68,68,.2);  color: #f87171; }
        .cat-pii_leakage       { background: rgba(168,85,247,.2); color: #c084fc; }
        .cat-toxicity          { background: rgba(249,115,22,.2); color: #fb923c; }
        .cat-prompt_injection  { background: rgba(59,130,246,.2); color: #60a5fa; }
        .cat-model_spec_violation { background: rgba(34,197,94,.2); color: #4ade80; }

        .badge-sev {
            font-size: .6rem; font-weight: 700; padding: .1rem .45rem;
            border-radius: 999px;
        }
        .sev-CRITICAL { background: rgba(239,68,68,.3); color: #fca5a5; }
        .sev-HIGH     { background: rgba(249,115,22,.3); color: #fdba74; }
        .sev-MEDIUM   { background: rgba(234,179,8,.3);  color: #fde047; }

        .sc-desc { font-size: .78rem; color: #94a3b8; line-height: 1.5; }

        /* ── Arena ───────────────────────────────────────────────────────── */
        .arena { display: flex; flex-direction: column; overflow: hidden; }

        .arena-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap;
        }
        .arena-title { font-size: 1.1rem; font-weight: 700; }
        .arena-desc  { font-size: .8rem; color: var(--muted); margin-top: .15rem; }

        .controls { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
        .ctrl-select {
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            color: var(--text); padding: .4rem .75rem; border-radius: .4rem;
            font-size: .78rem; font-family: inherit; cursor: pointer;
        }
        .ctrl-select:focus { outline: none; border-color: var(--orange); }

        .btn-run {
            background: linear-gradient(135deg, var(--red), var(--orange));
            color: #fff; border: none; padding: .45rem 1.25rem; border-radius: .4rem;
            font-weight: 700; font-size: .82rem; cursor: pointer; letter-spacing: .04em;
            transition: opacity .15s, transform .1s; white-space: nowrap;
        }
        .btn-run:hover   { opacity: .9; }
        .btn-run:active  { transform: scale(.97); }
        .btn-run:disabled{ opacity: .45; cursor: not-allowed; }

        .arena-body { flex: 1; overflow-y: auto; padding: 1.2rem 1.5rem; }

        /* ── Empty state ─────────────────────────────────────────────────── */
        .empty-state {
            height: 100%; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: .75rem; color: var(--muted);
        }
        .empty-state .icon { font-size: 3rem; opacity: .3; }

        /* ── Turn Card ───────────────────────────────────────────────────── */
        .turn-card {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: .75rem; margin-bottom: 1rem; overflow: hidden;
            animation: slideIn .3s ease;
        }
        @keyframes slideIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; } }

        .turn-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: .6rem 1rem; background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border);
            font-size: .7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: var(--muted);
        }
        .turn-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; }
        @media(max-width:900px){ .turn-grid { grid-template-columns: 1fr; } }

        .turn-panel { padding: .9rem 1rem; border-right: 1px solid var(--border); }
        .turn-panel:last-child { border-right: none; }

        .tp-label {
            font-size: .62rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .1em; margin-bottom: .5rem; display: flex; align-items: center; gap: .35rem;
        }
        .tp-dot { width: .45rem; height: .45rem; border-radius: 50%; flex-shrink: 0; }

        .technique-badge {
            display: inline-block; font-size: .62rem; font-weight: 600;
            padding: .1rem .5rem; border-radius: .25rem;
            background: rgba(239,68,68,.15); color: #f87171; margin-bottom: .5rem;
        }

        .tp-text {
            font-family: var(--mono); font-size: .72rem;
            color: #cbd5e1; line-height: 1.6;
            white-space: pre-wrap; word-break: break-word; max-height: 10rem; overflow-y: auto;
        }
        .tp-reasoning { font-size: .72rem; color: var(--muted); font-style: italic; margin-top: .4rem; line-height: 1.5; }

        /* Verdict badge */
        .verdict-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .7rem; font-weight: 700; padding: .25rem .7rem;
            border-radius: .35rem; letter-spacing: .06em;
        }
        .verdict-BLOCK  { background: rgba(239,68,68,.2);  color: #f87171; }
        .verdict-ALLOW  { background: rgba(34,197,94,.2);  color: #4ade80; }
        .verdict-MODIFY { background: rgba(234,179,8,.2);  color: #fde047; }

        .outcome-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .68rem; font-weight: 700; padding: .15rem .55rem;
            border-radius: .25rem;
        }
        .outcome-red_team_win  { background: rgba(239,68,68,.2);  color: #f87171; }
        .outcome-blue_team_win { background: rgba(59,130,246,.2); color: #60a5fa; }
        .outcome-draw          { background: rgba(234,179,8,.2);  color: #fde047; }
        .outcome-false_positive{ background: rgba(168,85,247,.2); color: #c084fc; }

        .owasp-tag { font-size: .65rem; color: var(--muted); margin-top: .35rem; }

        /* ── Scoreboard ──────────────────────────────────────────────────── */
        .scoreboard {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: .75rem; padding: 1rem 1.25rem; margin-bottom: 1rem;
            display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;
        }
        .score-item { text-align: center; }
        .score-num  { font-size: 1.6rem; font-weight: 800; }
        .score-lbl  { font-size: .65rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }

        /* ── Loader ──────────────────────────────────────────────────────── */
        .loader-row { display: flex; align-items: center; gap: .75rem; color: var(--muted); font-size: .82rem; padding: .75rem 0; }
        .spinner { width: 1.1rem; height: 1.1rem; border: 2px solid rgba(255,255,255,0.1); border-top-color: var(--orange); border-radius: 50%; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Summary Card ────────────────────────────────────────────────── */
        .summary-card {
            background: linear-gradient(135deg, rgba(34,197,94,0.07), rgba(59,130,246,0.05));
            border: 1px solid rgba(34,197,94,0.2); border-radius: .75rem; padding: 1.2rem;
            margin-bottom: 1rem; animation: slideIn .3s ease;
        }
        .summary-title { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #4ade80; margin-bottom: .75rem; }
        .summary-grid  { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .75rem; }
        .summary-stat  { background: rgba(0,0,0,.3); padding: .6rem .8rem; border-radius: .4rem; }
        .summary-stat .val { font-size: 1.3rem; font-weight: 800; }
        .summary-stat .key { font-size: .65rem; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }

        /* scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 99px; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-logo">
        <div class="logo-icon">⚔</div>
        <div>
            <h1>Red-Team <span>Arena</span></h1>
            <div class="header-subtitle">Autonomous Multi-Agent Adversarial Simulation</div>
        </div>
    </div>
    <div style="font-size:.72rem;color:var(--muted);">Phase 1 — Foundation</div>
</header>

<div class="layout" x-data="duelApp()">

    <!-- ── Sidebar ───────────────────────────────────────────────── -->
    <aside class="sidebar">
        <div class="sidebar-title">Scenarios ({{ count($scenarios) }})</div>
        @foreach($scenarios as $s)
        <div class="scenario-card"
             :class="{ active: activeId === '{{ $s->id }}' }"
             @click="select('{{ $s->id }}', '{{ addslashes($s->description) }}', '{{ $s->category }}')">
            <div class="sc-header">
                <span class="badge-cat cat-{{ $s->category }}">{{ $s->category }}</span>
                <span class="badge-sev sev-{{ $s->metadata['severity'] ?? 'MEDIUM' }}">{{ $s->metadata['severity'] ?? 'N/A' }}</span>
            </div>
            <div class="sc-desc">{{ $s->description }}</div>
        </div>
        @endforeach
    </aside>

    <!-- ── Arena ─────────────────────────────────────────────────── -->
    <main class="arena">

        <!-- Controls -->
        <div class="arena-header">
            <div>
                <div class="arena-title" x-text="activeId ? activeCategory.replace('_',' ').toUpperCase() : 'Select a Scenario'"></div>
                <div class="arena-desc" x-text="activeId ? activeDesc : 'Choose a scenario from the left panel to begin.'"></div>
            </div>
            <div class="controls">
                <select class="ctrl-select" x-model="policyProfile">
                    <option value="strict">Strict Policy</option>
                    <option value="moderate">Moderate Policy</option>
                    <option value="permissive">Permissive Policy</option>
                </select>
                <select class="ctrl-select" x-model="targetModel">
                    <option value="llama3-8b-8192">Groq · llama3-8b</option>
                    <option value="llama3-70b-8192">Groq · llama3-70b</option>
                    <option value="mixtral-8x7b-32768">Groq · Mixtral 8x7B</option>
                </select>
                <select class="ctrl-select" x-model="maxTurns">
                    <option value="2">2 Turns</option>
                    <option value="3" selected>3 Turns</option>
                    <option value="5">5 Turns</option>
                </select>
                <button class="btn-run" @click="run()" :disabled="!activeId || running">
                    <span x-show="!running">⚡ Commence Attack</span>
                    <span x-show="running" class="loader-row" style="padding:0;gap:.45rem;">
                        <span class="spinner"></span> Running duel…
                    </span>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="arena-body">

            <!-- Empty state -->
            <template x-if="!activeId && turns.length === 0">
                <div class="empty-state">
                    <div class="icon">⚔️</div>
                    <div style="font-weight:600;">No Scenario Selected</div>
                    <div style="font-size:.8rem;">Choose a scenario from the sidebar to begin a duel.</div>
                </div>
            </template>

            <!-- Summary -->
            <template x-if="summary">
                <div class="summary-card">
                    <div class="summary-title">⚡ Duel Complete — Summary</div>
                    <div class="summary-grid">
                        <div class="summary-stat">
                            <div class="val" style="color:var(--red)" x-text="summary.red_team_wins"></div>
                            <div class="key">Red Wins</div>
                        </div>
                        <div class="summary-stat">
                            <div class="val" style="color:var(--blue)" x-text="summary.blue_team_wins"></div>
                            <div class="key">Blue Wins</div>
                        </div>
                        <div class="summary-stat">
                            <div class="val" style="color:var(--yellow)" x-text="summary.draws"></div>
                            <div class="key">Draws</div>
                        </div>
                        <div class="summary-stat">
                            <div class="val" x-text="(summary.attack_success_rate * 100).toFixed(0) + '%'"></div>
                            <div class="key">Attack Success</div>
                        </div>
                        <div class="summary-stat">
                            <div class="val" style="color:var(--green)" x-text="(summary.defense_effectiveness * 100).toFixed(0) + '%'"></div>
                            <div class="key">Defense Effectiveness</div>
                        </div>
                        <div class="summary-stat">
                            <div class="val" x-text="(summary.owasp_categories_triggered || []).join(', ') || 'N/A'"></div>
                            <div class="key">OWASP Categories</div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Turn cards -->
            <template x-for="t in turns" :key="t.turn">
                <div class="turn-card">
                    <div class="turn-header">
                        <span>Turn <span x-text="t.turn"></span></span>
                        <div style="display:flex;gap:.5rem;align-items:center;">
                            <span x-show="t.latency_ms" style="color:var(--muted);font-weight:400;" x-text="t.latency_ms + 'ms'"></span>
                            <span class="outcome-badge" :class="'outcome-' + (t.judge_outcome || 'draw')" x-text="(t.judge_outcome || 'pending').replace('_',' ')"></span>
                            <span class="owasp-tag" x-text="t.owasp_category || ''"></span>
                        </div>
                    </div>
                    <div class="turn-grid">

                        <!-- Attacker panel -->
                        <div class="turn-panel">
                            <div class="tp-label" style="color:var(--red);">
                                <span class="tp-dot" style="background:var(--red);"></span> ATTACKER
                            </div>
                            <div class="technique-badge" x-text="t.attacker_technique"></div>
                            <div class="tp-text" x-text="t.adversarial_prompt"></div>
                            <div class="tp-reasoning" x-text="t.attacker_reasoning"></div>
                        </div>

                        <!-- Model panel -->
                        <div class="turn-panel">
                            <div class="tp-label" style="color:var(--blue);">
                                <span class="tp-dot" style="background:var(--blue);"></span> TARGET MODEL
                            </div>
                            <div class="tp-text" x-text="t.model_response"></div>
                            <template x-if="t.guardrail_input_result && t.guardrail_input_result.risk_score > 0">
                                <div class="tp-reasoning">
                                    Risk Score: <span x-text="t.guardrail_input_result.risk_score"></span>
                                    <span x-text="t.guardrail_input_result.scanners_triggered && t.guardrail_input_result.scanners_triggered.length ? ' · ' + t.guardrail_input_result.scanners_triggered.join(', ') : ''"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Defender panel -->
                        <div class="turn-panel">
                            <div class="tp-label" style="color:var(--green);">
                                <span class="tp-dot" style="background:var(--green);"></span> DEFENDER
                            </div>
                            <div style="margin-bottom:.5rem;">
                                <span class="verdict-badge" :class="'verdict-' + (t.defender_verdict || 'BLOCK')" x-text="t.defender_verdict || 'BLOCK'"></span>
                            </div>
                            <div class="tp-reasoning" x-text="t.defender_reasoning"></div>
                            <template x-if="t.modified_response">
                                <div class="tp-text" style="color:var(--yellow);margin-top:.5rem;" x-text="'Modified: ' + t.modified_response"></div>
                            </template>
                        </div>

                    </div>
                </div>
            </template>

            <!-- Loading indicator -->
            <template x-if="running">
                <div class="loader-row">
                    <span class="spinner"></span>
                    <span>Agents are dueling… This may take 10–30 seconds per turn.</span>
                </div>
            </template>

        </div>
    </main>
</div>

<script>
function duelApp() {
    return {
        activeId: null,
        activeDesc: '',
        activeCategory: '',
        policyProfile: 'strict',
        targetModel: 'llama3-8b-8192',
        maxTurns: 3,
        running: false,
        turns: [],
        summary: null,

        select(id, desc, category) {
            this.activeId       = id;
            this.activeDesc     = desc;
            this.activeCategory = category;
            this.turns          = [];
            this.summary        = null;
        },

        async run() {
            if (!this.activeId || this.running) return;
            this.running = true;
            this.turns   = [];
            this.summary = null;

            try {
                const res = await fetch(`/duels/${this.activeId}/run`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        max_turns:      this.maxTurns,
                        policy_profile: this.policyProfile,
                        target_model:   this.targetModel,
                        provider:       'groq'
                    })
                });

                const data = await res.json();
                this.turns   = data.turns    || [];
                this.summary = data.summary  || null;
            } catch (e) {
                console.error('Duel error:', e);
            } finally {
                this.running = false;
            }
        }
    };
}
</script>
</body>
</html>
