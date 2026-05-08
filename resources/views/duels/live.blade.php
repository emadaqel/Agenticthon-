<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Duel — Red-Team Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root{
            --bg:#030712; --surface:#0d1117; --panel:#111827; --border:rgba(255,255,255,.08);
            --border2:rgba(255,255,255,.15); --text:#f1f5f9; --muted:#64748b; --muted2:#94a3b8;
            --red:#ef4444; --red-d:#dc2626; --orange:#f97316; --blue:#3b82f6;
            --green:#22c55e; --yellow:#eab308; --purple:#a855f7; --cyan:#06b6d4;
            --mono:'JetBrains Mono',monospace;
            --arena-font:'Orbitron',sans-serif;
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

        /* Arena bg */
        .arena-bg{position:fixed;inset:0;z-index:-1;pointer-events:none;}
        .arena-bg::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%) perspective(500px) rotateX(55deg);width:150%;height:300px;background:repeating-linear-gradient(90deg,rgba(239,68,68,.03) 0px,transparent 1px,transparent 80px),repeating-linear-gradient(0deg,rgba(59,130,246,.03) 0px,transparent 1px,transparent 80px);opacity:.5;mask-image:linear-gradient(to top,black 20%,transparent);-webkit-mask-image:linear-gradient(to top,black 20%,transparent);}
        .arena-bg::after{content:'';position:absolute;top:10%;left:-5%;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(239,68,68,.06),transparent 70%);filter:blur(80px);}

        /* Unified Combat HUD */
        .combat-hud{background:var(--surface);border:1px solid var(--border);border-radius:.75rem;padding:1rem 1.5rem;margin-bottom:1.25rem;}
        .combat-hud-top{display:flex;align-items:center;justify-content:center;gap:1.5rem;}
        .fighter-side{display:flex;align-items:center;gap:.65rem;flex:1;}
        .fighter-side.blue{flex-direction:row-reverse;text-align:right;}
        .fighter-avatar{width:2.5rem;height:2.5rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
        .fighter-avatar.red{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);box-shadow:0 0 12px rgba(239,68,68,.12);}
        .fighter-avatar.blue{background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);box-shadow:0 0 12px rgba(59,130,246,.12);}
        .fighter-info{flex:1;min-width:0;}
        .fighter-name{font-family:var(--arena-font);font-size:.6rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;}
        .fighter-name.red{color:#fca5a5;}
        .fighter-name.blue{color:#93c5fd;}
        .fighter-wins{font-size:.65rem;color:var(--muted2);font-weight:700;margin-top:.15rem;}
        .hp-bar{height:5px;border-radius:99px;background:rgba(255,255,255,.08);overflow:hidden;margin-top:.25rem;}
        .hp-fill{height:100%;border-radius:99px;transition:width .6s ease;}
        .hp-fill.red{background:linear-gradient(90deg,#dc2626,#ef4444);}
        .hp-fill.blue{background:linear-gradient(90deg,#1d4ed8,#3b82f6);}
        .vs-badge{font-family:var(--arena-font);font-size:1rem;font-weight:900;color:var(--orange);text-shadow:0 0 18px rgba(249,115,22,.35);flex-shrink:0;}
        .combat-progress{margin-top:.6rem;}
        .combat-progress-bar{height:3px;border-radius:99px;background:rgba(255,255,255,.05);overflow:hidden;}
        .combat-progress-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--blue),var(--purple));transition:width .5s ease;}
        .combat-status{display:flex;align-items:center;justify-content:space-between;margin-top:.3rem;font-size:.62rem;color:var(--muted);}

        /* Round announce */
        @keyframes roundSlam{0%{opacity:0;transform:scale(3);}50%{opacity:1;transform:scale(.95);}100%{transform:scale(1);}}
        .round-announce{text-align:center;padding:.35rem 0;}
        .round-announce span{font-family:var(--arena-font);font-size:.85rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:var(--orange);text-shadow:0 0 20px rgba(249,115,22,.35);animation:roundSlam .5s ease both;}

        @keyframes fighter-idle{0%,100%{transform:translateY(0);}50%{transform:translateY(-4px);}}
        @keyframes energy-line{0%{background-position:200% center;}100%{background-position:-200% center;}}
        @keyframes particle-rise{0%{transform:translateY(0) scale(1);opacity:.5;}100%{transform:translateY(-100px) scale(0);opacity:0;}}
        .particles{position:fixed;inset:0;pointer-events:none;z-index:-1;overflow:hidden;}
        .particle{position:absolute;width:3px;height:3px;border-radius:50%;animation:particle-rise linear infinite;}

        @media(prefers-reduced-motion:reduce){.arena-bg::before,.particle,.round-announce span{animation:none!important;}}

        a.back-link{color:var(--muted2);font-size:.72rem;text-decoration:none;border:1px solid var(--border2);
            padding:.28rem .7rem;border-radius:.3rem;transition:all .2s;}
        a.back-link:hover{color:var(--text);background:rgba(255,255,255,.06);}

        /* ── Main layout ── */
        .main{max-width:1100px;margin:0 auto;padding:1.25rem;}

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
        .turns-feed{display:flex;flex-direction:column;gap:1rem;}

        .turn-card{
            background:var(--surface);border:1px solid var(--border);border-radius:.75rem;
            overflow:hidden;animation:slideIn .4s ease forwards;
            opacity:0;transform:translateY(12px);
            border-left:3px solid transparent;
        }
        .turn-card.red-win{border-left-color:var(--red);}
        .turn-card.blue-win{border-left-color:var(--blue);}
        @keyframes slideIn{to{opacity:1;transform:translateY(0);}}

        .turn-header{
            padding:.5rem .85rem;display:flex;align-items:center;gap:.6rem;
            border-bottom:1px solid var(--border);background:rgba(0,0,0,.2);
        }
        .turn-num{
            width:1.6rem;height:1.6rem;border-radius:.3rem;
            background:rgba(255,255,255,.07);border:1px solid var(--border2);
            font-size:.65rem;font-weight:800;font-family:var(--mono);
            display:flex;align-items:center;justify-content:center;flex-shrink:0;
        }
        .turn-title{font-weight:700;font-size:.72rem;flex:1;}
        .turn-time{font-size:.6rem;color:var(--muted);font-family:var(--mono);}

        .outcome-badge{padding:.18rem .55rem;border-radius:.3rem;font-size:.6rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;}
        .outcome-red_team_win{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.3);}
        .outcome-blue_team_win{background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.3);}
        .outcome-draw{background:rgba(234,179,8,.1);color:#fde047;border:1px solid rgba(234,179,8,.25);}
        .outcome-false_positive{background:rgba(168,85,247,.1);color:#c084fc;border:1px solid rgba(168,85,247,.25);}

        /* ── Combat exchange (2-column) ── */
        .combat-exchange{display:grid;grid-template-columns:1fr 1fr;}
        .combat-col{padding:.75rem .85rem;}
        .combat-col:first-child{border-right:1px solid var(--border);}
        .combat-col-label{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.4rem;display:flex;align-items:center;gap:.35rem;}
        .combat-col-label.red{color:#fca5a5;}
        .combat-col-label.blue{color:#a78bfa;}
        .technique-tag{font-size:.58rem;color:var(--muted2);font-family:var(--mono);background:rgba(255,255,255,.05);padding:.12rem .4rem;border-radius:.2rem;border:1px solid var(--border);margin-bottom:.35rem;display:inline-block;}
        .prompt-text{font-family:var(--mono);font-size:.72rem;color:var(--text);line-height:1.6;word-break:break-word;max-height:120px;overflow-y:auto;}
        .verdict-inline{display:flex;align-items:center;gap:.4rem;margin-bottom:.3rem;}

        /* ── Details strip (compact) ── */
        .details-strip{padding:.5rem .85rem;background:rgba(0,0,0,.15);border-top:1px solid var(--border);display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;font-size:.65rem;color:var(--muted2);}
        .details-strip .detail-item{display:flex;align-items:center;gap:.25rem;}
        .details-strip .detail-label{color:var(--muted);font-weight:600;}
        .detail-expand{background:none;border:1px solid var(--border);color:var(--muted2);padding:.15rem .45rem;border-radius:.25rem;font-size:.6rem;cursor:pointer;transition:all .2s;font-family:'Inter',sans-serif;}
        .detail-expand:hover{background:rgba(255,255,255,.05);color:var(--text);}
        .reasoning-panel{padding:.6rem .85rem;background:rgba(0,0,0,.1);border-top:1px solid var(--border);font-size:.7rem;color:var(--muted2);line-height:1.6;}

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
        .summary-title{font-family:var(--arena-font);font-size:1.1rem;font-weight:900;letter-spacing:.08em;margin-bottom:1rem;
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

<div class="arena-bg"></div>
<div class="particles" id="live-particles"></div>

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

    <!-- Unified Combat HUD -->
    <div class="combat-hud" x-show="status !== 'not_found'">
        <div class="combat-hud-top">
            <div class="fighter-side">
                <div class="fighter-avatar red" style="animation:fighter-idle 3s ease infinite;">🔴</div>
                <div class="fighter-info">
                    <div class="fighter-name red">ATTACKER</div>
                    <div class="fighter-wins">Wins: <span x-text="redWins">0</span></div>
                    <div class="hp-bar"><div class="hp-fill red" :style="`width:${redHpPct}%`"></div></div>
                </div>
            </div>
            <div class="vs-badge">VS</div>
            <div class="fighter-side blue">
                <div class="fighter-avatar blue" style="animation:fighter-idle 3s ease infinite;animation-delay:.5s;">🔵</div>
                <div class="fighter-info">
                    <div class="fighter-name blue">DEFENDER</div>
                    <div class="fighter-wins">Wins: <span x-text="blueWins">0</span></div>
                    <div class="hp-bar"><div class="hp-fill blue" :style="`width:${blueHpPct}%`"></div></div>
                </div>
            </div>
        </div>
        <div class="combat-progress">
            <div class="combat-progress-bar"><div class="combat-progress-fill" :style="`width:${progressPct}%`"></div></div>
            <div class="combat-status">
                <span x-show="status==='running'">⚔ Fighting<span class="thinking-dots"></span></span>
                <span x-show="status==='complete'">✅ Complete</span>
                <span x-show="status==='waiting'">⏳ Queued</span>
                <span x-text="'Duel ' + duelId"></span>
            </div>
        </div>
    </div>

    <!-- Waiting spinner -->
    <div class="waiting-box" x-show="status==='waiting' || (status==='running' && turns.length===0)">
        <div class="waiting-spinner"></div>
        <div class="waiting-title">Agents Initialising<span class="thinking-dots"></span></div>
        <div class="waiting-sub">Attacker Agent is crafting the first adversarial prompt via Groq AI</div>
    </div>

    <!-- Round Announce -->
    <template x-if="status==='running' && turns.length > 0">
        <div class="round-announce"><span x-text="'ROUND ' + turns.length + ' COMPLETE'"></span></div>
    </template>

    <!-- Live turns feed -->
    <div class="turns-feed">
        <template x-for="(turn, idx) in turns" :key="turn.turn">
            <div class="turn-card" :class="{'red-win':turn.judge_outcome==='red_team_win','blue-win':turn.judge_outcome==='blue_team_win'}">
                <!-- Turn header -->
                <div class="turn-header">
                    <div class="turn-num" x-text="'T' + turn.turn"></div>
                    <div class="turn-title">Turn <span x-text="turn.turn"></span></div>
                    <span class="technique-tag" x-text="turn.attacker_technique || '—'"></span>
                    <span class="turn-time" x-text="formatMs(turn.latency_ms)"></span>
                    <span class="outcome-badge" :class="'outcome-' + (turn.judge_outcome||'draw')" x-text="(turn.judge_outcome||'draw').replace('_',' ').toUpperCase()"></span>
                </div>

                <!-- 2-column combat exchange: Attacker vs Defender -->
                <div class="combat-exchange">
                    <!-- Left: Attacker -->
                    <div class="combat-col">
                        <div class="combat-col-label red">🔴 Attacker</div>
                        <div class="prompt-text" x-text="turn.adversarial_prompt || '—'"></div>
                    </div>
                    <!-- Right: Defender -->
                    <div class="combat-col">
                        <div class="combat-col-label blue">🔵 Defender</div>
                        <div class="verdict-inline">
                            <span class="outcome-badge" :class="'outcome-' + (turn.judge_outcome||'draw')" x-text="turn.defender_verdict || 'BLOCK'"></span>
                            <span class="owasp-pill" x-text="turn.owasp_category || 'LLM01'"></span>
                        </div>
                        <template x-if="turn.defender_reasoning">
                            <div style="font-size:.68rem;color:var(--muted2);line-height:1.5;max-height:80px;overflow-y:auto;" x-text="turn.defender_reasoning"></div>
                        </template>
                    </div>
                </div>

                <!-- Compact details strip -->
                <div class="details-strip">
                    <!-- Model response -->
                    <template x-if="turn.model_response && turn.model_response !== '—BLOCKED BEFORE MODEL—' && turn.model_response !== '—BLOCKED—'">
                        <span class="detail-item"><span class="detail-label">🤖 Model:</span> <span x-text="(turn.model_response||'').substring(0,60) + ((turn.model_response||'').length>60?'…':'')"></span></span>
                    </template>
                    <template x-if="turn.model_response === '—BLOCKED BEFORE MODEL—' || turn.model_response === '—BLOCKED—'">
                        <span class="blocked-pill">⛔ Blocked at input</span>
                    </template>
                    <!-- Guardrail badges -->
                    <span class="detail-item">
                        <span class="detail-label">🛡 In:</span>
                        <span class="risk-badge" :class="riskClass(turn.risk_score_input)" x-text="turn.risk_score_input != null ? Math.round(turn.risk_score_input*100)+'%' : 'N/A'"></span>
                    </span>
                    <span class="detail-item" x-show="turn.risk_score_output != null">
                        <span class="detail-label">Out:</span>
                        <span class="risk-badge" :class="riskClass(turn.risk_score_output)" x-text="turn.risk_score_output != null ? Math.round(turn.risk_score_output*100)+'%' : ''"></span>
                    </span>
                    <span class="detail-item" x-show="turn.tokens_used">
                        <span class="detail-label">Tokens:</span> <span x-text="turn.tokens_used"></span>
                    </span>
                    <!-- Expand reasoning -->
                    <button class="detail-expand" @click="turn._expanded = !turn._expanded" x-text="turn._expanded ? '▲ Hide' : '▼ Details'"></button>
                </div>

                <!-- Expandable reasoning panel -->
                <template x-if="turn._expanded">
                    <div class="reasoning-panel">
                        <template x-if="turn.model_response && turn.model_response !== '—BLOCKED BEFORE MODEL—'">
                            <div style="margin-bottom:.5rem;"><strong style="color:#93c5fd;">🤖 Full Response:</strong><br><span x-text="turn.model_response"></span></div>
                        </template>
                        <template x-if="turn.attacker_reasoning">
                            <div style="margin-bottom:.5rem;"><strong style="color:#fca5a5;">🔴 Attacker Reasoning:</strong> <span x-text="turn.attacker_reasoning"></span></div>
                        </template>
                        <template x-if="turn.judge_reasoning">
                            <div><strong style="color:#fde047;">⚖ Judge:</strong> <span x-text="turn.judge_reasoning"></span></div>
                        </template>
                    </div>
                </template>
            </div>
        </template>

        <!-- Live thinking indicator -->
        <template x-if="status === 'running' && turns.length > 0">
            <div class="turn-card" style="border-color:rgba(59,130,246,.2);opacity:1;transform:none;">
                <div class="turn-header">
                    <div class="turn-num" x-text="'T' + (turns.length + 1)"></div>
                    <div class="turn-title">Processing Turn <span x-text="turns.length + 1"></span><span class="thinking-dots"></span></div>
                </div>
                <div style="padding:.75rem .85rem;display:flex;align-items:center;gap:.6rem;font-size:.72rem;color:var(--muted);">
                    <div class="waiting-spinner" style="width:1rem;height:1rem;margin:0;"></div>
                    🔴 Crafting adversarial prompt<span class="thinking-dots"></span>
                </div>
            </div>
        </template>
    </div>

    <!-- Summary card (shown when complete) -->
    <template x-if="summary && status === 'complete'">
        <div class="summary-card">
            <div class="summary-title">⚔ MATCH COMPLETE</div>
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
        get redHpPct() {
            if (this.turns.length === 0) return 100;
            const redLosses = this.turns.filter(t => t.judge_outcome === 'blue_team_win').length;
            return Math.max(5, 100 - (redLosses / Math.max(1, this.turns.length)) * 100);
        },
        get blueHpPct() {
            if (this.turns.length === 0) return 100;
            const blueLosses = this.turns.filter(t => t.judge_outcome === 'red_team_win').length;
            return Math.max(5, 100 - (blueLosses / Math.max(1, this.turns.length)) * 100);
        },
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
<script>
(function(){
    const c=document.getElementById('live-particles');
    if(!c||window.matchMedia('(prefers-reduced-motion:reduce)').matches) return;
    const cols=['rgba(239,68,68,.5)','rgba(59,130,246,.5)','rgba(249,115,22,.4)'];
    for(let i=0;i<14;i++){const p=document.createElement('div');p.className='particle';p.style.left=Math.random()*100+'%';p.style.bottom=Math.random()*15+'%';p.style.background=cols[i%3];p.style.animationDuration=(6+Math.random()*8)+'s';p.style.animationDelay=Math.random()*5+'s';p.style.width=(2+Math.random()*2)+'px';p.style.height=p.style.width;p.style.boxShadow='0 0 5px '+cols[i%3];c.appendChild(p);}
})();
</script>
</body>
</html>
