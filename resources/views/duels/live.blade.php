<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Duel — Red-Team Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root{
            --bg:#030712; --surface:#0d1117; --panel:#111827; --border:rgba(255,255,255,.08);
            --border2:rgba(255,255,255,.15); --text:#f1f5f9; --muted:#64748b; --muted2:#94a3b8;
            --red:#ef4444; --red-d:#dc2626; --orange:#f97316; --blue:#3b82f6;
            --green:#22c55e; --yellow:#eab308; --purple:#a855f7; --cyan:#06b6d4;
            --mono:'JetBrains Mono',monospace;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html{font-size:14px;scroll-behavior:smooth;}
        body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;}
        ::-webkit-scrollbar{width:5px;} ::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:9px;}

        /* ── Top bar ── */
        .topbar{
            position:sticky;top:0;z-index:50;
            background:rgba(3,7,18,.9);backdrop-filter:blur(20px);
            border-bottom:1px solid var(--border);
            padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;
        }
        .brand{display:flex;align-items:center;gap:.6rem;}
        .brand-icon{width:2rem;height:2rem;border-radius:.4rem;background:linear-gradient(135deg,#dc2626,#f97316);
            display:flex;align-items:center;justify-content:center;font-size:.9rem;box-shadow:0 0 14px rgba(239,68,68,.35);}
        .brand-name{font-weight:800;font-size:.9rem;letter-spacing:-.02em;}
        .brand-name em{font-style:normal;color:var(--orange);}

        .meta{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;}
        .chip{padding:.22rem .65rem;border-radius:999px;border:1px solid var(--border2);
            font-size:.65rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;}
        .chip-red{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#fca5a5;}
        .chip-blue{background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.3);color:#93c5fd;}
        .chip-green{background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.3);color:#86efac;}
        .chip-yellow{background:rgba(234,179,8,.1);border-color:rgba(234,179,8,.3);color:#fde047;}
        .chip-purple{background:rgba(168,85,247,.1);border-color:rgba(168,85,247,.3);color:#c084fc;}
        .chip-muted{background:rgba(148,163,184,.07);border-color:rgba(148,163,184,.2);color:var(--muted2);}

        .status-running{display:flex;align-items:center;gap:.4rem;color:#86efac;font-size:.72rem;font-weight:700;}
        .pulse-dot{width:.5rem;height:.5rem;border-radius:50%;background:#22c55e;animation:pulseGreen 1s ease infinite;}
        @keyframes pulseGreen{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.4);}50%{box-shadow:0 0 0 6px rgba(34,197,94,0);}}
        .status-complete{color:#86efac;font-size:.72rem;font-weight:700;}
        .status-idle{color:var(--muted2);font-size:.72rem;font-weight:700;}

        a.back-link{color:var(--muted2);font-size:.72rem;text-decoration:none;border:1px solid var(--border2);
            padding:.28rem .7rem;border-radius:.3rem;transition:all .2s;}
        a.back-link:hover{color:var(--text);background:rgba(255,255,255,.06);}

        /* ── Main layout ── */
        .main{max-width:1200px;margin:0 auto;padding:1.5rem;}

        /* ── Progress header ── */
        .progress-header{
            background:var(--surface);border:1px solid var(--border);border-radius:.75rem;
            padding:1.25rem 1.5rem;margin-bottom:1.5rem;
            display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center;
        }
        .progress-title{font-size:1.1rem;font-weight:800;letter-spacing:-.02em;margin-bottom:.35rem;}
        .progress-sub{font-size:.72rem;color:var(--muted2);}
        .progress-sub span{color:var(--text);font-weight:600;}
        .progress-bar-wrap{background:rgba(255,255,255,.05);border-radius:999px;height:.35rem;margin-top:.75rem;overflow:hidden;}
        .progress-bar{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--blue),var(--purple));transition:width .5s ease;}

        .score-grid{display:flex;gap:1.25rem;}
        .score-card{text-align:center;}
        .score-val{font-size:1.6rem;font-weight:800;font-family:var(--mono);line-height:1;}
        .score-val.red{color:var(--red);}
        .score-val.blue{color:var(--blue);}
        .score-val.muted{color:var(--muted2);}
        .score-label{font-size:.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:.2rem;}

        /* ── Waiting state ── */
        .waiting-box{
            text-align:center;padding:4rem 2rem;
            background:var(--surface);border:1px solid var(--border);border-radius:.75rem;
            margin-bottom:1.5rem;
        }
        .waiting-spinner{
            width:3rem;height:3rem;border:3px solid rgba(59,130,246,.2);border-top-color:var(--blue);
            border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 1.25rem;
        }
        @keyframes spin{to{transform:rotate(360deg);}}
        .waiting-title{font-size:1rem;font-weight:700;margin-bottom:.5rem;}
        .waiting-sub{font-size:.78rem;color:var(--muted2);}
        .thinking-dots::after{content:'...';animation:dots 1.5s steps(4,end) infinite;}
        @keyframes dots{0%,20%{content:''}21%,40%{content:'.'}41%,60%{content:'..'}61%,80%,100%{content:'...'}}

        /* ── Turn cards ── */
        .turns-feed{display:flex;flex-direction:column;gap:1.25rem;}

        .turn-card{
            background:var(--surface);border:1px solid var(--border);border-radius:.75rem;
            overflow:hidden;
            animation:slideIn .4s ease forwards;
            opacity:0;transform:translateY(16px);
        }
        @keyframes slideIn{to{opacity:1;transform:translateY(0);}}

        .turn-header{
            padding:.65rem 1rem;display:flex;align-items:center;gap:.75rem;
            border-bottom:1px solid var(--border);background:rgba(0,0,0,.2);
        }
        .turn-num{
            width:1.8rem;height:1.8rem;border-radius:.35rem;
            background:rgba(255,255,255,.07);border:1px solid var(--border2);
            font-size:.7rem;font-weight:800;font-family:var(--mono);
            display:flex;align-items:center;justify-content:center;flex-shrink:0;
        }
        .turn-title{font-weight:700;font-size:.78rem;flex:1;}
        .turn-time{font-size:.62rem;color:var(--muted);font-family:var(--mono);}

        .outcome-badge{padding:.2rem .6rem;border-radius:.3rem;font-size:.62rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;}
        .outcome-red_team_win{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.3);}
        .outcome-blue_team_win{background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.3);}
        .outcome-draw{background:rgba(234,179,8,.1);color:#fde047;border:1px solid rgba(234,179,8,.25);}
        .outcome-false_positive{background:rgba(168,85,247,.1);color:#c084fc;border:1px solid rgba(168,85,247,.25);}

        /* ── Turn body rows ── */
        .turn-body{display:flex;flex-direction:column;}

        .agent-row{
            display:grid;grid-template-columns:180px 1fr;
            border-bottom:1px solid var(--border);
        }
        .agent-row:last-child{border-bottom:none;}

        .agent-label{
            padding:.85rem 1rem;
            display:flex;flex-direction:column;justify-content:flex-start;gap:.35rem;
            border-right:1px solid var(--border);background:rgba(0,0,0,.15);
            min-width:0;
        }
        .agent-name{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;}
        .agent-name.attacker{color:#fca5a5;}
        .agent-name.model{color:#93c5fd;}
        .agent-name.guardrail{color:#d9f99d;}
        .agent-name.defender{color:#a78bfa;}
        .agent-name.judge{color:#fde047;}
        .agent-icon{font-size:1.1rem;}
        .technique-tag{font-size:.6rem;color:var(--muted2);font-family:var(--mono);
            background:rgba(255,255,255,.05);padding:.15rem .4rem;border-radius:.25rem;
            border:1px solid var(--border);word-break:break-all;}

        .agent-content{
            padding:.85rem 1rem;font-size:.78rem;line-height:1.7;color:var(--muted2);
        }
        .agent-content .prompt-text{
            font-family:var(--mono);font-size:.73rem;color:var(--text);
            background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15);
            padding:.6rem .8rem;border-radius:.4rem;line-height:1.65;word-break:break-word;
        }
        .agent-content .response-text{
            font-size:.78rem;color:var(--text);line-height:1.7;word-break:break-word;
        }
        .agent-content .verdict-text{
            font-family:var(--mono);font-size:.73rem;line-height:1.6;word-break:break-word;
        }

        /* guardrail scan badges */
        .scan-row{display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;}
        .risk-badge{
            padding:.18rem .55rem;border-radius:.3rem;font-size:.65rem;font-weight:700;
            font-family:var(--mono);
        }
        .risk-low{background:rgba(34,197,94,.12);color:#86efac;}
        .risk-med{background:rgba(234,179,8,.12);color:#fde047;}
        .risk-high{background:rgba(239,68,68,.15);color:#fca5a5;}
        .scanner-tag{
            font-size:.62rem;padding:.12rem .4rem;border-radius:.25rem;
            background:rgba(168,85,247,.1);color:#c084fc;border:1px solid rgba(168,85,247,.2);
        }
        .blocked-pill{
            font-size:.62rem;padding:.15rem .5rem;border-radius:.25rem;font-weight:700;
            background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.25);
        }
        .pass-pill{
            font-size:.62rem;padding:.15rem .5rem;border-radius:.25rem;font-weight:700;
            background:rgba(34,197,94,.1);color:#86efac;border:1px solid rgba(34,197,94,.2);
        }

        .verdict-BLOCK{color:#fca5a5;font-weight:800;}
        .verdict-ALLOW{color:#86efac;font-weight:800;}
        .verdict-MODIFY{color:#fde047;font-weight:800;}

        /* ── "Thinking" live row ── */
        .thinking-row{
            display:grid;grid-template-columns:180px 1fr;
            border-bottom:1px solid var(--border);
            animation:slideIn .3s ease forwards;opacity:0;
        }
        .thinking-content{
            padding:.85rem 1rem;display:flex;align-items:center;gap:.6rem;
            font-size:.75rem;color:var(--muted);
        }
        .thinking-spinner{
            width:1rem;height:1rem;border:2px solid rgba(255,255,255,.1);border-top-color:var(--blue);
            border-radius:50%;animation:spin .7s linear infinite;flex-shrink:0;
        }

        /* ── Summary card ── */
        .summary-card{
            background:linear-gradient(135deg,rgba(59,130,246,.07),rgba(168,85,247,.07));
            border:1px solid rgba(59,130,246,.2);border-radius:.75rem;
            padding:1.5rem;margin-top:1.5rem;
            animation:slideIn .5s ease forwards;opacity:0;
        }
        .summary-title{font-size:1rem;font-weight:800;margin-bottom:1rem;
            background:linear-gradient(135deg,var(--blue),var(--purple));
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1rem;margin-bottom:1rem;}
        .summary-stat{background:rgba(255,255,255,.04);border:1px solid var(--border);
            border-radius:.5rem;padding:.85rem;text-align:center;}
        .summary-stat-val{font-size:1.5rem;font-weight:800;font-family:var(--mono);}
        .summary-stat-label{font-size:.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:.25rem;}
        .summary-actions{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem;}
        .btn-action{
            padding:.5rem 1.1rem;border-radius:.4rem;font-size:.75rem;font-weight:700;
            text-decoration:none;border:1px solid;cursor:pointer;transition:all .2s;
            font-family:'Inter',sans-serif;
        }
        .btn-blue{background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.35);color:#93c5fd;}
        .btn-blue:hover{background:rgba(59,130,246,.2);}
        .btn-purple{background:rgba(168,85,247,.1);border-color:rgba(168,85,247,.35);color:#c084fc;}
        .btn-purple:hover{background:rgba(168,85,247,.2);}
        .btn-green{background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.3);color:#86efac;}
        .btn-green:hover{background:rgba(34,197,94,.18);}

        /* ── OWASP pills ── */
        .owasp-pills{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem;}
        .owasp-pill{
            padding:.18rem .55rem;border-radius:.3rem;font-size:.65rem;font-weight:700;
            font-family:var(--mono);
            background:rgba(6,182,212,.1);color:#67e8f9;border:1px solid rgba(6,182,212,.2);
        }

        /* ── Empty / error states ── */
        .error-box{
            background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);
            color:#fca5a5;border-radius:.5rem;padding:1rem;font-size:.78rem;
            margin-bottom:1rem;
        }
    </style>
</head>
<body x-data="liveApp()" x-init="init()">

<!-- ── Top Bar ── -->
<div class="topbar">
    <div class="brand">
        <div class="brand-icon">⚔</div>
        <div>
            <div class="brand-name">Red-Team <em>Arena</em></div>
        </div>
    </div>

    <div class="meta">
        <span class="chip chip-muted" x-text="meta.scenario || 'Loading...'"></span>
        <span class="chip chip-blue" x-text="meta.model || '...'"></span>
        <span class="chip" :class="{'chip-red':'permissive'===meta.policy,'chip-green':'strict'===meta.policy,'chip-yellow':'moderate'===meta.policy}" x-text="meta.policy || '...'"></span>

        <div x-show="status==='running'" class="status-running">
            <div class="pulse-dot"></div>
            <span>LIVE · Turn <span x-text="turns.length + 1"></span></span>
        </div>
        <div x-show="status==='complete'" class="status-complete">✓ COMPLETE</div>
        <div x-show="status==='waiting'" class="status-idle">Waiting for queue...</div>
    </div>

    <a href="/duels" class="back-link">← Back to Arena</a>
</div>

<!-- ── Main ── -->
<div class="main">

    <!-- Error -->
    <template x-if="error">
        <div class="error-box" x-text="error"></div>
    </template>

    <!-- Progress header -->
    <div class="progress-header" x-show="status !== 'not_found'">
        <div>
            <div class="progress-title">
                <span x-show="status==='running'">⚔ Duel in Progress<span class="thinking-dots"></span></span>
                <span x-show="status==='complete'">✅ Duel Complete</span>
                <span x-show="status==='waiting'">⏳ Queued — waiting for worker</span>
            </div>
            <div class="progress-sub">
                Duel ID: <span x-text="duelId"></span>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar" :style="`width:${progressPct}%`"></div>
            </div>
        </div>
        <div class="score-grid">
            <div class="score-card">
                <div class="score-val red" x-text="redWins">0</div>
                <div class="score-label">🔴 Red</div>
            </div>
            <div class="score-card">
                <div class="score-val muted">vs</div>
                <div class="score-label">&nbsp;</div>
            </div>
            <div class="score-card">
                <div class="score-val blue" x-text="blueWins">0</div>
                <div class="score-label">🔵 Blue</div>
            </div>
        </div>
    </div>

    <!-- Waiting spinner (before first turn arrives) -->
    <div class="waiting-box" x-show="status==='waiting' || (status==='running' && turns.length===0)">
        <div class="waiting-spinner"></div>
        <div class="waiting-title">Agents Initialising<span class="thinking-dots"></span></div>
        <div class="waiting-sub">Attacker Agent is crafting the first adversarial prompt via Groq AI</div>
    </div>

    <!-- Live turns feed -->
    <div class="turns-feed">
        <template x-for="(turn, idx) in turns" :key="turn.turn">
            <div class="turn-card">
                <!-- Turn header -->
                <div class="turn-header">
                    <div class="turn-num" x-text="'T' + turn.turn"></div>
                    <div class="turn-title">Turn <span x-text="turn.turn"></span></div>
                    <span class="chip chip-muted" x-text="turn.attacker_technique || '—'" style="font-size:.6rem;"></span>
                    <span class="owasp-pill" x-text="turn.owasp_category || 'LLM01'"></span>
                    <span class="turn-time" x-text="formatMs(turn.latency_ms)"></span>
                    <span class="outcome-badge" :class="'outcome-' + (turn.judge_outcome||'draw')" x-text="(turn.judge_outcome||'draw').replace('_',' ').toUpperCase()"></span>
                </div>

                <div class="turn-body">
                    <!-- 1. Attacker -->
                    <div class="agent-row">
                        <div class="agent-label">
                            <span class="agent-icon">🔴</span>
                            <span class="agent-name attacker">Attacker Agent</span>
                            <span class="technique-tag" x-text="turn.attacker_technique || 'unknown'"></span>
                        </div>
                        <div class="agent-content">
                            <div class="prompt-text" x-text="turn.adversarial_prompt || '—'"></div>
                            <template x-if="turn.attacker_reasoning">
                                <div style="margin-top:.5rem;font-size:.7rem;color:var(--muted);font-style:italic;" x-text="'↳ ' + turn.attacker_reasoning"></div>
                            </template>
                        </div>
                    </div>

                    <!-- 2. Guardrail Input scan -->
                    <div class="agent-row">
                        <div class="agent-label">
                            <span class="agent-icon">🛡</span>
                            <span class="agent-name guardrail">Input Guard</span>
                        </div>
                        <div class="agent-content">
                            <div class="scan-row" x-data="{}">
                                <template x-if="(turn.guardrail_input_result?.blocked ?? false)">
                                    <span class="blocked-pill">⛔ BLOCKED BY NEMO</span>
                                </template>
                                <template x-if="!(turn.guardrail_input_result?.blocked ?? false)">
                                    <span class="pass-pill">✓ PASSED</span>
                                </template>
                                <span class="risk-badge" :class="riskClass(turn.risk_score_input)"
                                    x-text="'Risk ' + (turn.risk_score_input != null ? Math.round(turn.risk_score_input*100)+'%' : 'N/A')"></span>
                                <template x-for="s in (turn.guardrail_input_result?.scanners_triggered || [])">
                                    <span class="scanner-tag" x-text="s"></span>
                                </template>
                            </div>
                            <template x-if="turn.guardrail_input_result?.explanation">
                                <div style="margin-top:.4rem;font-size:.68rem;color:var(--muted);" x-text="turn.guardrail_input_result.explanation"></div>
                            </template>
                        </div>
                    </div>

                    <!-- 3. Target Model response -->
                    <template x-if="turn.model_response && turn.model_response !== '—BLOCKED BEFORE MODEL—' && turn.model_response !== '—BLOCKED—'">
                        <div class="agent-row">
                            <div class="agent-label">
                                <span class="agent-icon">🤖</span>
                                <span class="agent-name model">Target Model</span>
                                <span class="technique-tag" x-text="meta.model || '—'"></span>
                            </div>
                            <div class="agent-content">
                                <div class="response-text" x-text="turn.model_response"></div>
                                <div style="margin-top:.4rem;font-size:.67rem;color:var(--muted);">
                                    <span x-text="turn.tokens_used ? turn.tokens_used + ' tokens' : ''"></span>
                                    <span x-show="turn.latency_ms" x-text="' · ' + turn.latency_ms + 'ms'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="turn.model_response === '—BLOCKED BEFORE MODEL—' || turn.model_response === '—BLOCKED—'">
                        <div class="agent-row">
                            <div class="agent-label">
                                <span class="agent-icon">🤖</span>
                                <span class="agent-name model">Target Model</span>
                            </div>
                            <div class="agent-content">
                                <span class="blocked-pill">⛔ NEVER REACHED — BLOCKED AT INPUT</span>
                            </div>
                        </div>
                    </template>

                    <!-- 4. Guardrail Output scan -->
                    <template x-if="turn.model_response && turn.model_response !== '—BLOCKED BEFORE MODEL—' && turn.model_response !== '—BLOCKED—'">
                        <div class="agent-row">
                            <div class="agent-label">
                                <span class="agent-icon">🔍</span>
                                <span class="agent-name guardrail">Output Guard</span>
                            </div>
                            <div class="agent-content">
                                <div class="scan-row">
                                    <span class="risk-badge" :class="riskClass(turn.risk_score_output)"
                                        x-text="'Risk ' + (turn.risk_score_output != null ? Math.round(turn.risk_score_output*100)+'%' : 'N/A')"></span>
                                    <template x-for="s in (turn.guardrail_output_result?.scanners_triggered || [])">
                                        <span class="scanner-tag" x-text="s"></span>
                                    </template>
                                    <template x-if="(turn.guardrail_output_result?.scanners_triggered || []).length === 0">
                                        <span class="pass-pill">✓ CLEAN</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- 5. Defender verdict -->
                    <div class="agent-row">
                        <div class="agent-label">
                            <span class="agent-icon">🔵</span>
                            <span class="agent-name defender">Defender Agent</span>
                        </div>
                        <div class="agent-content">
                            <span class="verdict-text">
                                Verdict: <span :class="'verdict-' + (turn.defender_verdict||'BLOCK')" x-text="turn.defender_verdict || 'BLOCK'"></span>
                            </span>
                            <template x-if="turn.defender_reasoning">
                                <div style="margin-top:.45rem;font-size:.72rem;color:var(--muted2);" x-text="turn.defender_reasoning"></div>
                            </template>
                            <template x-if="turn.modified_response">
                                <div style="margin-top:.4rem;font-size:.72rem;color:#fde047;">
                                    Modified: <span x-text="turn.modified_response"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 6. Judge outcome -->
                    <div class="agent-row">
                        <div class="agent-label">
                            <span class="agent-icon">⚖</span>
                            <span class="agent-name judge">Policy Judge</span>
                        </div>
                        <div class="agent-content">
                            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                                <span class="outcome-badge" :class="'outcome-' + (turn.judge_outcome||'draw')"
                                    x-text="(turn.judge_outcome||'draw').replace(/_/g,' ').toUpperCase()"></span>
                                <span class="owasp-pill" x-text="turn.owasp_category || 'LLM01'"></span>
                            </div>
                            <template x-if="turn.judge_reasoning">
                                <div style="margin-top:.45rem;font-size:.72rem;color:var(--muted2);" x-text="turn.judge_reasoning"></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Live "thinking" indicator — shows while next turn is processing -->
        <template x-if="status === 'running' && turns.length > 0">
            <div class="turn-card" style="border-color:rgba(59,130,246,.2);">
                <div class="turn-header">
                    <div class="turn-num" x-text="'T' + (turns.length + 1)"></div>
                    <div class="turn-title">Processing Turn <span x-text="turns.length + 1"></span><span class="thinking-dots"></span></div>
                </div>
                <div class="thinking-row" style="opacity:1;">
                    <div class="agent-label">
                        <span class="agent-icon">🔴</span>
                        <span class="agent-name attacker">Attacker Agent</span>
                    </div>
                    <div class="thinking-content">
                        <div class="thinking-spinner"></div>
                        Crafting adversarial prompt via Groq AI<span class="thinking-dots"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Summary card (shown when complete) -->
    <template x-if="summary && status === 'complete'">
        <div class="summary-card">
            <div class="summary-title">⚔ Duel Summary</div>
            <div class="summary-grid">
                <div class="summary-stat">
                    <div class="summary-stat-val" style="color:var(--red)" x-text="summary.red_team_wins">0</div>
                    <div class="summary-stat-label">Red Wins</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val" style="color:var(--blue)" x-text="summary.blue_team_wins">0</div>
                    <div class="summary-stat-label">Blue Wins</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val" style="color:var(--muted2)" x-text="summary.draws">0</div>
                    <div class="summary-stat-label">Draws</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val" :style="(summary.attack_success_rate||0)>0.5?'color:var(--red)':'color:var(--green)'"
                        x-text="Math.round((summary.attack_success_rate||0)*100) + '%'">0%</div>
                    <div class="summary-stat-label">Attack Success</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val" :style="(summary.defense_effectiveness||0)>0.5?'color:var(--green)':'color:var(--orange)'"
                        x-text="Math.round((summary.defense_effectiveness||0)*100) + '%'">0%</div>
                    <div class="summary-stat-label">Defense Eff.</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-stat-val" style="color:var(--purple)" x-text="summary.total_turns">0</div>
                    <div class="summary-stat-label">Total Turns</div>
                </div>
            </div>

            <template x-if="(summary.owasp_categories_triggered||[]).length > 0">
                <div>
                    <div style="font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;">OWASP Categories Triggered</div>
                    <div class="owasp-pills">
                        <template x-for="cat in (summary.owasp_categories_triggered||[])">
                            <span class="owasp-pill" x-text="cat"></span>
                        </template>
                    </div>
                </div>
            </template>

            <div class="summary-actions">
                <a :href="'/duels/' + duelId + '/report/html'" target="_blank" class="btn-action btn-purple">📄 Export Report</a>
                <a href="/duels" class="btn-action btn-blue">← Back to Arena</a>
                <button class="btn-action btn-green" @click="runAgain()">⚔ Run Again</button>
            </div>
        </div>
    </template>

</div>

<script>
const DUEL_ID = @json($duelId);

function liveApp() {
    return {
        duelId: DUEL_ID,
        status: 'waiting',
        turns: [],
        summary: null,
        error: null,
        meta: {},
        pollInterval: null,
        knownTurns: 0,

        get redWins()  { return this.turns.filter(t => t.judge_outcome === 'red_team_win').length; },
        get blueWins() { return this.turns.filter(t => t.judge_outcome === 'blue_team_win').length; },
        get progressPct() {
            if (this.status === 'complete') return 100;
            if (!this.summary) return this.turns.length > 0 ? Math.min(90, this.turns.length * 25) : 5;
            return 100;
        },

        init() {
            this.poll();
            this.pollInterval = setInterval(() => this.poll(), 1500);
        },

        async poll() {
            try {
                const r = await fetch(`/duels/${this.duelId}/status`);
                if (!r.ok) { this.error = 'Could not reach status endpoint.'; return; }
                const data = await r.json();

                if (data.status === 'not_found') {
                    this.error = 'Duel not found. It may have expired from cache.';
                    clearInterval(this.pollInterval);
                    return;
                }

                this.status = data.status || 'waiting';

                // Capture meta from first response
                if (data.scenario && !this.meta.scenario) {
                    this.meta = {
                        scenario: data.scenario,
                        model:    data.model,
                        policy:   data.policy,
                        provider: data.provider,
                    };
                }

                // Only update turns if new ones arrived (avoid re-render flicker)
                const incoming = data.turns || [];
                if (incoming.length > this.knownTurns) {
                    this.turns = incoming;
                    this.knownTurns = incoming.length;
                    // Auto-scroll to bottom
                    this.$nextTick(() => {
                        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                    });
                }

                if (data.summary) this.summary = data.summary;

                if (this.status === 'complete') {
                    clearInterval(this.pollInterval);
                }
            } catch(e) {
                // network hiccup — keep polling
            }
        },

        riskClass(score) {
            if (score == null) return 'risk-low';
            if (score >= 0.6) return 'risk-high';
            if (score >= 0.3) return 'risk-med';
            return 'risk-low';
        },

        formatMs(ms) {
            if (!ms) return '';
            if (ms >= 1000) return (ms/1000).toFixed(1) + 's';
            return ms + 'ms';
        },

        runAgain() {
            window.location.href = '/duels';
        },
    };
}
</script>
</body>
</html>
