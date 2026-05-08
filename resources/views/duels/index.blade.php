<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red-Team Arena — Security Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --red:#ef4444; --red-d:#dc2626; --red-g:rgba(239,68,68,.3);
            --orange:#f97316; --orange-g:rgba(249,115,22,.25);
            --blue:#3b82f6; --blue-d:#1d4ed8; --blue-g:rgba(59,130,246,.25);
            --green:#22c55e; --cyan:#06b6d4; --yellow:#eab308; --purple:#a855f7;
            --bg:#030712; --surface:#0d1117; --panel:#111827; --panel2:#0f172a;
            --border:rgba(255,255,255,.08); --border2:rgba(255,255,255,.14);
            --text:#f1f5f9; --muted:#64748b; --muted2:#94a3b8;
            --mono:'JetBrains Mono',monospace;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html{font-size:14px;}
        body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;overflow:hidden;}
        ::-webkit-scrollbar{width:4px;height:4px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:99px;}

        /* ── Health Banner ── */
        .health-banner{
            background:rgba(13,17,23,.95);border-bottom:1px solid var(--border);
            padding:.4rem 1.25rem;display:flex;align-items:center;gap:1.25rem;
            font-size:.68rem;overflow-x:auto;white-space:nowrap;
        }
        .health-banner span{font-weight:700;color:var(--muted2);letter-spacing:.06em;text-transform:uppercase;margin-right:.25rem;}
        .svc{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;border-radius:999px;border:1px solid transparent;}
        .svc-online{background:rgba(34,197,94,.08);border-color:rgba(34,197,94,.25);color:#86efac;}
        .svc-offline{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.25);color:#fca5a5;}
        .svc-configured{background:rgba(59,130,246,.08);border-color:rgba(59,130,246,.25);color:#93c5fd;}
        .svc-checking{background:rgba(148,163,184,.08);border-color:rgba(148,163,184,.15);color:var(--muted2);}
        .svc-dot{width:.4rem;height:.4rem;border-radius:50%;flex-shrink:0;}
        .svc-online .svc-dot{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.2);}
        .svc-offline .svc-dot{background:var(--red);}
        .svc-configured .svc-dot{background:var(--blue);}
        .svc-checking .svc-dot{background:var(--muted);animation:pulse-d .9s ease infinite alternate;}
        @keyframes pulse-d{from{opacity:.3;}to{opacity:1;}}

        /* ── Top Bar ── */
        .topbar{
            background:rgba(13,17,23,.92);backdrop-filter:blur(20px);
            border-bottom:1px solid var(--border);padding:.75rem 1.25rem;
            display:flex;align-items:center;justify-content:space-between;gap:1rem;
        }
        .brand{display:flex;align-items:center;gap:.6rem;}
        .brand-icon{width:2.2rem;height:2.2rem;border-radius:.45rem;background:linear-gradient(135deg,var(--red-d),var(--orange));display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;box-shadow:0 0 15px var(--red-g);}
        .brand-name{font-size:.95rem;font-weight:800;letter-spacing:-.02em;}
        .brand-name em{font-style:normal;color:var(--orange);}
        .brand-sub{font-size:.6rem;color:var(--muted);letter-spacing:.04em;}

        /* ── Nav Tabs ── */
        .nav-tabs{display:flex;gap:.15rem;background:rgba(0,0,0,.3);border:1px solid var(--border);border-radius:.5rem;padding:.18rem;}
        .tab-btn{padding:.35rem .8rem;border-radius:.35rem;border:none;background:transparent;color:var(--muted2);font-size:.72rem;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap;font-family:'Inter',sans-serif;letter-spacing:.02em;}
        .tab-btn:hover{color:var(--text);background:rgba(255,255,255,.05);}
        .tab-btn.active{background:rgba(255,255,255,.1);color:var(--text);}

        .topbar-right{display:flex;align-items:center;gap:.75rem;}
        .demo-badge{background:rgba(168,85,247,.1);border:1px solid rgba(168,85,247,.3);color:#c084fc;padding:.25rem .65rem;border-radius:.3rem;font-size:.65rem;font-weight:700;letter-spacing:.06em;}
        .btn-sm{border:1px solid var(--border2);background:rgba(255,255,255,.04);color:var(--muted2);padding:.3rem .75rem;border-radius:.35rem;font-size:.72rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:'Inter',sans-serif;}
        .btn-sm:hover{background:rgba(255,255,255,.08);color:var(--text);}
        .btn-sm-red{border-color:rgba(239,68,68,.3);color:#f87171;}
        .btn-sm-red:hover{background:rgba(239,68,68,.08);}
        .btn-sm-green{border-color:rgba(34,197,94,.3);color:#86efac;}
        .btn-sm-green:hover{background:rgba(34,197,94,.08);}
        .btn-sm-purple{border-color:rgba(168,85,247,.3);color:#c084fc;}
        .btn-sm-purple:hover{background:rgba(168,85,247,.08);}

        /* ── App Layout ── */
        .app-body{height:calc(100vh - 73px);display:flex;flex-direction:column;}
        .tab-content{flex:1;overflow:hidden;display:none;}
        .tab-content.active{display:flex;}

        /* ── ARENA TAB ── */
        .arena-layout{display:grid;grid-template-columns:340px 1fr;height:100%;overflow:hidden;}

        /* sidebar */
        .sidebar{border-right:1px solid var(--border);overflow-y:auto;background:rgba(13,17,23,.7);}
        .sidebar-hdr{padding:.75rem 1rem .55rem;font-size:.6rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);position:sticky;top:0;background:rgba(13,17,23,.97);z-index:1;display:flex;align-items:center;justify-content:space-between;}
        .sidebar-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;padding:.65rem 1rem;border-bottom:1px solid var(--border);}
        .sb-stat{background:rgba(0,0,0,.3);border:1px solid var(--border);border-radius:.5rem;padding:.5rem .6rem;text-align:center;}
        .sb-stat strong{display:block;font-size:1.05rem;font-weight:800;}
        .sb-stat span{font-size:.58rem;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;}

        .sc-card{padding:.85rem 1rem;border-bottom:1px solid var(--border);cursor:pointer;transition:all .15s;border-left:3px solid transparent;}
        .sc-card:hover{background:rgba(59,130,246,.06);border-left-color:rgba(59,130,246,.3);}
        .sc-card.active{background:rgba(59,130,246,.1);border-left-color:var(--blue);}
        .sc-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem;}
        .cat-badge{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.12rem .45rem;border-radius:.2rem;}
        .cat-jailbreak,.cat-self_harm{background:rgba(239,68,68,.18);color:#f87171;}
        .cat-pii_leakage{background:rgba(168,85,247,.18);color:#c084fc;}
        .cat-toxicity{background:rgba(249,115,22,.18);color:#fb923c;}
        .cat-prompt_injection{background:rgba(59,130,246,.18);color:#60a5fa;}
        .cat-model_spec_violation{background:rgba(34,197,94,.18);color:#4ade80;}
        .sev-badge{font-size:.58rem;font-weight:700;padding:.1rem .4rem;border-radius:999px;}
        .sev-CRITICAL{background:rgba(239,68,68,.2);color:#fca5a5;}
        .sev-HIGH{background:rgba(249,115,22,.2);color:#fdba74;}
        .sev-MEDIUM{background:rgba(234,179,8,.2);color:#fde047;}
        .sev-LOW{background:rgba(34,197,94,.2);color:#86efac;}
        .sc-desc{font-size:.75rem;color:var(--muted2);line-height:1.5;}
        .sc-chips{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.55rem;}
        .chip{border:1px solid rgba(148,163,184,.15);color:#94a3b8;border-radius:999px;padding:.14rem .45rem;font-size:.6rem;}

        /* arena main */
        .arena-main{display:flex;flex-direction:column;overflow:hidden;}
        .arena-ctrl{padding:.85rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;background:rgba(13,17,23,.6);}
        .ctrl-title{font-size:.95rem;font-weight:700;}
        .ctrl-desc{font-size:.75rem;color:var(--muted);margin-top:.1rem;}
        .ctrl-row{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;}
        .ctrl-select{background:rgba(13,17,23,.95);border:1px solid var(--border2);color:var(--text);padding:.32rem .65rem;border-radius:.4rem;font-size:.73rem;font-family:'Inter',sans-serif;cursor:pointer;outline:none;}
        .ctrl-select:focus{border-color:var(--blue);}
        .btn-run{background:linear-gradient(135deg,var(--blue-d),var(--cyan));color:#fff;border:none;padding:.38rem 1.1rem;border-radius:.4rem;font-weight:700;font-size:.75rem;cursor:pointer;transition:all .15s;white-space:nowrap;letter-spacing:.03em;font-family:'Inter',sans-serif;}
        .btn-run:hover{opacity:.9;transform:translateY(-1px);}
        .btn-run:disabled{opacity:.4;cursor:not-allowed;transform:none;}
        .btn-compare{background:linear-gradient(135deg,var(--purple),#7c3aed);color:#fff;border:none;padding:.38rem 1.1rem;border-radius:.4rem;font-weight:700;font-size:.75rem;cursor:pointer;transition:all .15s;font-family:'Inter',sans-serif;}
        .btn-compare:hover{opacity:.9;}

        .arena-body{flex:1;overflow-y:auto;padding:1rem 1.25rem;}

        .kpi-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:.6rem;margin-bottom:1rem;}
        .kpi{background:var(--panel);border:1px solid var(--border);border-radius:.6rem;padding:.75rem 1rem;}
        .kpi .lbl{font-size:.58rem;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:.25rem;}
        .kpi .val{font-size:1.3rem;font-weight:800;}

        /* selected scenario panel */
        .sel-panel{border:1px solid rgba(59,130,246,.25);background:linear-gradient(135deg,rgba(59,130,246,.07),rgba(13,17,23,.6));border-radius:.75rem;padding:.9rem 1.1rem;margin-bottom:.85rem;display:flex;justify-content:space-between;align-items:center;gap:.75rem;}
        .sel-panel h3{font-size:.92rem;font-weight:700;margin-bottom:.25rem;}
        .sel-panel p{font-size:.75rem;color:var(--muted2);line-height:1.55;}

        /* turn cards */
        .turn-card{background:rgba(13,17,23,.8);border:1px solid var(--border);border-radius:.75rem;margin-bottom:.85rem;overflow:hidden;animation:slide-in .25s ease;}
        @keyframes slide-in{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}
        .turn-hdr{display:flex;align-items:center;justify-content:space-between;padding:.5rem .9rem;background:rgba(255,255,255,.025);border-bottom:1px solid var(--border);font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);}
        .turn-grid{display:grid;grid-template-columns:1fr 1fr 1fr;}
        .tp{padding:.75rem .9rem;border-right:1px solid var(--border);}
        .tp:last-child{border-right:none;}
        .tp-lbl{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;margin-bottom:.4rem;display:flex;align-items:center;gap:.3rem;}
        .tp-dot{width:.4rem;height:.4rem;border-radius:50%;flex-shrink:0;}
        .tech-pill{display:inline-block;font-size:.6rem;font-weight:600;padding:.1rem .45rem;border-radius:.2rem;background:rgba(239,68,68,.12);color:#f87171;margin-bottom:.4rem;}
        .tp-text{font-family:var(--mono);font-size:.68rem;color:#cbd5e1;line-height:1.65;white-space:pre-wrap;word-break:break-word;max-height:9rem;overflow-y:auto;}
        .tp-note{font-size:.68rem;color:var(--muted);font-style:italic;margin-top:.35rem;line-height:1.5;}

        .verdict-pill{display:inline-flex;align-items:center;gap:.3rem;font-size:.66rem;font-weight:700;padding:.2rem .6rem;border-radius:.3rem;letter-spacing:.05em;}
        .v-BLOCK{background:rgba(239,68,68,.15);color:#f87171;}
        .v-ALLOW{background:rgba(34,197,94,.15);color:#4ade80;}
        .v-MODIFY{background:rgba(234,179,8,.15);color:#fde047;}
        .outcome-pill{display:inline-flex;align-items:center;gap:.3rem;font-size:.63rem;font-weight:700;padding:.15rem .5rem;border-radius:.25rem;}
        .o-red_team_win{background:rgba(239,68,68,.15);color:#f87171;}
        .o-blue_team_win{background:rgba(59,130,246,.15);color:#60a5fa;}
        .o-draw{background:rgba(234,179,8,.15);color:#fde047;}
        .o-false_positive{background:rgba(168,85,247,.15);color:#c084fc;}

        /* remediation box */
        .rem-box{border:1px solid rgba(239,68,68,.25);background:rgba(239,68,68,.06);border-radius:.65rem;padding:.85rem 1rem;margin-top:.5rem;}
        .rem-title{font-size:.7rem;font-weight:700;color:#f87171;margin-bottom:.4rem;display:flex;align-items:center;gap:.4rem;}
        .rem-list{list-style:none;}
        .rem-list li{font-size:.7rem;color:var(--muted2);padding:.18rem 0;padding-left:1rem;position:relative;}
        .rem-list li::before{content:'→';position:absolute;left:0;color:var(--muted);}

        /* summary card */
        .summary-card{background:linear-gradient(135deg,rgba(34,197,94,.07),rgba(59,130,246,.05));border:1px solid rgba(34,197,94,.2);border-radius:.75rem;padding:1rem 1.2rem;margin-bottom:.85rem;animation:slide-in .3s ease;}
        .summary-title{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#4ade80;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem;justify-content:space-between;}
        .summary-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.6rem;}
        .stat-box{background:rgba(0,0,0,.3);border-radius:.4rem;padding:.55rem .75rem;}
        .stat-box .val{font-size:1.25rem;font-weight:800;}
        .stat-box .key{font-size:.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}

        /* empty */
        .empty{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.6rem;color:var(--muted);}
        .empty .icon{font-size:3.5rem;opacity:.2;}

        /* loading */
        .loader{display:flex;align-items:center;gap:.65rem;color:var(--muted);font-size:.78rem;padding:.65rem 0;}
        .spin{width:1rem;height:1rem;border:2px solid rgba(255,255,255,.08);border-top-color:var(--orange);border-radius:50%;animation:spin .7s linear infinite;}
        @keyframes spin{to{transform:rotate(360deg);}}
        .error-box{border:1px solid rgba(239,68,68,.3);background:rgba(239,68,68,.08);color:#fca5a5;border-radius:.6rem;padding:.75rem 1rem;margin-bottom:.85rem;font-size:.78rem;}

        /* ── DASHBOARD TAB ── */
        .dash-layout{width:100%;overflow-y:auto;padding:1.25rem;}
        .dash-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.5rem;}
        .dash-kpi{background:var(--panel);border:1px solid var(--border);border-radius:.75rem;padding:1.1rem 1.25rem;}
        .dash-kpi .dk-val{font-size:2rem;font-weight:900;line-height:1;}
        .dash-kpi .dk-lbl{font-size:.65rem;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-top:.3rem;}
        .dash-kpi .dk-sub{font-size:.72rem;color:var(--muted2);margin-top:.25rem;}
        .dash-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem;}
        .dash-card{background:var(--panel);border:1px solid var(--border);border-radius:.75rem;padding:1.1rem 1.25rem;}
        .dash-card-full{grid-column:1/-1;}
        .dc-title{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.85rem;}

        /* heatmap */
        .heatmap-wrap{overflow-x:auto;}
        .heatmap-grid{display:grid;gap:4px;}
        .hm-cell{padding:.5rem .6rem;border-radius:.3rem;font-size:.7rem;font-weight:700;text-align:center;min-width:80px;}
        .hm-header{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);padding:.5rem .6rem;text-align:center;}
        .hm-row-label{font-size:.65rem;color:var(--muted2);display:flex;align-items:center;justify-content:flex-end;padding-right:.5rem;font-weight:600;}
        .hm-empty{background:rgba(255,255,255,.04);color:var(--muted);font-size:.65rem;}

        /* bar chart */
        .bar-item{display:flex;align-items:center;gap:.65rem;margin-bottom:.55rem;}
        .bar-label{font-size:.72rem;color:var(--muted2);min-width:130px;text-align:right;}
        .bar-track{flex:1;height:.55rem;background:rgba(255,255,255,.06);border-radius:999px;overflow:hidden;}
        .bar-fill{height:100%;border-radius:999px;transition:width .5s ease;}
        .bar-val{font-size:.68rem;font-weight:700;color:var(--muted2);min-width:35px;}

        /* ── HISTORY TAB ── */
        .history-layout{width:100%;overflow-y:auto;padding:1.25rem;}
        .tbl{width:100%;border-collapse:collapse;}
        .tbl th{font-size:.62rem;text-transform:uppercase;letter-spacing:.09em;font-weight:700;color:var(--muted);padding:.65rem .85rem;border-bottom:2px solid var(--border);text-align:left;background:rgba(0,0,0,.2);}
        .tbl td{padding:.6rem .85rem;border-bottom:1px solid var(--border);font-size:.75rem;color:var(--muted2);}
        .tbl tr:hover td{background:rgba(255,255,255,.025);}
        .tbl-actions{display:flex;gap:.4rem;}

        /* ── COMPARE TAB ── */
        .compare-layout{width:100%;overflow-y:auto;padding:1.25rem;}
        .cmp-ctrl{background:var(--panel);border:1px solid var(--border);border-radius:.75rem;padding:1.1rem 1.25rem;margin-bottom:1rem;}
        .cmp-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;}
        .cmp-col{background:rgba(0,0,0,.2);border:1px solid var(--border);border-radius:.6rem;padding:.85rem 1rem;}
        .cmp-col-title{font-size:.8rem;font-weight:700;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid var(--border);}
        .winner-badge{background:linear-gradient(135deg,var(--green),var(--cyan));color:#fff;font-size:.65rem;font-weight:700;padding:.15rem .55rem;border-radius:999px;margin-left:.5rem;}

        /* ── SCENARIOS TAB ── */
        .scenarios-layout{width:100%;overflow-y:auto;padding:1.25rem;}
        .sc-builder{background:var(--panel);border:1px solid var(--border);border-radius:.75rem;padding:1.25rem;margin-bottom:1.25rem;}
        .sc-builder-title{font-size:.78rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
        .form-group{display:flex;flex-direction:column;gap:.3rem;}
        .form-group label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);}
        .form-control{background:rgba(0,0,0,.4);border:1px solid var(--border2);color:var(--text);padding:.45rem .7rem;border-radius:.4rem;font-size:.78rem;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;}
        .form-control:focus{border-color:var(--blue);}
        textarea.form-control{resize:vertical;min-height:80px;}
        .form-group-full{grid-column:1/-1;}
        .patterns-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem;margin-top:.35rem;}
        .pattern-chk{display:flex;align-items:center;gap:.4rem;font-size:.72rem;color:var(--muted2);cursor:pointer;}
        .pattern-chk input{accent-color:var(--blue);}

        .sc-list-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.85rem;}
        .sc-list-card{background:var(--panel);border:1px solid var(--border);border-radius:.7rem;padding:1rem 1.1rem;position:relative;}
        .sc-list-card .custom-tag{position:absolute;top:.65rem;right:.65rem;background:rgba(168,85,247,.12);border:1px solid rgba(168,85,247,.25);color:#c084fc;font-size:.58rem;font-weight:700;padding:.1rem .4rem;border-radius:999px;}

        /* ── REPORTS TAB ── */
        .reports-layout{width:100%;overflow-y:auto;padding:1.25rem;}
        .report-select{background:var(--panel);border:1px solid var(--border);border-radius:.75rem;padding:1.1rem 1.25rem;margin-bottom:1rem;}
        .report-preview{background:rgba(0,0,0,.2);border:1px solid var(--border);border-radius:.65rem;padding:1rem;font-size:.78rem;}
        .rp-row{display:flex;gap:.5rem;margin-bottom:.35rem;}
        .rp-label{color:var(--muted);min-width:140px;}
        .rp-val{color:var(--muted2);font-weight:600;}

        /* timeline modal */
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:500;display:flex;align-items:center;justify-content:center;padding:1.5rem;}
        .modal{background:var(--panel);border:1px solid var(--border2);border-radius:1rem;width:100%;max-width:860px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;}
        .modal-hdr{padding:.9rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
        .modal-hdr h3{font-size:.9rem;font-weight:700;}
        .modal-close{border:none;background:transparent;color:var(--muted2);cursor:pointer;font-size:1.2rem;line-height:1;padding:.2rem;}
        .modal-body{overflow-y:auto;padding:1.25rem;flex:1;}

        /* onboarding */
        .onboard-overlay{position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:999;display:flex;align-items:center;justify-content:center;padding:1.5rem;}
        .onboard{background:var(--panel);border:1px solid var(--border2);border-radius:1.25rem;width:100%;max-width:600px;padding:2.5rem;}
        .onboard-logo{display:flex;align-items:center;gap:.75rem;margin-bottom:2rem;}
        .onboard h2{font-size:1.5rem;font-weight:800;letter-spacing:-.03em;margin-bottom:.5rem;}
        .onboard p{color:var(--muted2);font-size:.88rem;line-height:1.7;margin-bottom:1.5rem;}
        .onboard-steps{display:flex;flex-direction:column;gap:.75rem;margin-bottom:2rem;}
        .onboard-step{display:flex;align-items:flex-start;gap:.85rem;}
        .onboard-step-num{width:1.6rem;height:1.6rem;border-radius:50%;background:linear-gradient(135deg,var(--red-d),var(--orange));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.72rem;color:#fff;flex-shrink:0;margin-top:.05rem;}
        .onboard-step-text{font-size:.82rem;color:var(--muted2);line-height:1.6;}
        .onboard-step-text strong{color:var(--text);}
        .onboard-actions{display:flex;gap:.75rem;}
        .btn-onboard-primary{background:linear-gradient(135deg,var(--red-d),var(--orange));color:#fff;border:none;padding:.6rem 1.75rem;border-radius:.5rem;font-weight:700;font-size:.85rem;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;box-shadow:0 0 25px var(--red-g);}
        .btn-onboard-primary:hover{opacity:.9;}
        .btn-onboard-secondary{background:rgba(255,255,255,.05);border:1px solid var(--border2);color:var(--muted2);padding:.6rem 1.75rem;border-radius:.5rem;font-weight:600;font-size:.85rem;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;}
        .btn-onboard-secondary:hover{background:rgba(255,255,255,.09);}
    </style>
</head>
<body x-data="arenaApp()" x-init="init()">

<!-- Onboarding Modal -->
<div class="onboard-overlay" x-show="showOnboard" x-transition.opacity style="display:none;">
    <div class="onboard">
        <div class="onboard-logo">
            <div class="brand-icon">⚔</div>
            <div>
                <div class="brand-name">Red-Team <em>Arena</em></div>
                <div class="brand-sub">Enterprise AI Security Validation</div>
            </div>
        </div>
        <h2>Welcome to the Security Console</h2>
        <p>Run autonomous adversarial simulations against your LLM deployments. Measure guardrail effectiveness, map results to OWASP LLM Top 10, and generate board-ready evidence trails.</p>
        <div class="onboard-steps">
            <div class="onboard-step">
                <div class="onboard-step-num">1</div>
                <div class="onboard-step-text"><strong>Select a threat scenario</strong> from the left panel — covering jailbreaks, PII leakage, prompt injection, and more.</div>
            </div>
            <div class="onboard-step">
                <div class="onboard-step-num">2</div>
                <div class="onboard-step-text"><strong>Configure your target model</strong> (Groq or HuggingFace), policy profile (strict/moderate/permissive), and number of turns.</div>
            </div>
            <div class="onboard-step">
                <div class="onboard-step-num">3</div>
                <div class="onboard-step-text"><strong>Hit "Commence Attack"</strong> and watch the red-team agent adapt in real time while the blue team defends.</div>
            </div>
            <div class="onboard-step">
                <div class="onboard-step-num">4</div>
                <div class="onboard-step-text"><strong>Review the results</strong> — heatmaps, compliance timelines, and executive reports are all one click away.</div>
            </div>
        </div>
        <div class="onboard-actions">
            <button class="btn-onboard-primary" @click="startDemo()">Load Demo Data & Explore →</button>
            <button class="btn-onboard-secondary" @click="showOnboard=false">Skip Onboarding</button>
        </div>
    </div>
</div>

<!-- Timeline Modal -->
<div class="modal-overlay" x-show="timelineOpen" x-transition.opacity @click.self="timelineOpen=false" style="display:none;">
    <div class="modal">
        <div class="modal-hdr">
            <h3>Compliance Evidence Timeline</h3>
            <button class="modal-close" @click="timelineOpen=false">✕</button>
        </div>
        <div class="modal-body">
            <div x-show="timelineLoading" class="loader"><span class="spin"></span> Loading timeline…</div>
            <template x-if="!timelineLoading && timelineTurns.length === 0">
                <div style="color:var(--muted);text-align:center;padding:2rem;">No turns found for this duel.</div>
            </template>
            <template x-for="t in timelineTurns" :key="t.id">
                <div class="turn-card" style="margin-bottom:.75rem;">
                    <div class="turn-hdr">
                        <span>Turn <span x-text="t.turn"></span></span>
                        <div style="display:flex;gap:.4rem;align-items:center;">
                            <span class="outcome-pill" :class="'o-'+(t.judge_outcome||'draw')" x-text="(t.judge_outcome||'draw').replace(/_/g,' ')"></span>
                            <span x-show="t.owasp_category" style="font-size:.62rem;color:var(--muted);" x-text="t.owasp_category"></span>
                            <span x-show="t.latency_ms" style="font-size:.62rem;color:var(--muted);" x-text="t.latency_ms+'ms'"></span>
                        </div>
                    </div>
                    <div class="turn-grid">
                        <div class="tp">
                            <div class="tp-lbl" style="color:var(--red);"><span class="tp-dot" style="background:var(--red);"></span>ATTACKER</div>
                            <div class="tech-pill" x-text="t.attacker_technique"></div>
                            <div class="tp-text" x-text="t.adversarial_prompt"></div>
                        </div>
                        <div class="tp">
                            <div class="tp-lbl" style="color:var(--blue);"><span class="tp-dot" style="background:var(--blue);"></span>MODEL RESPONSE</div>
                            <div class="tp-text" x-text="t.model_response"></div>
                            <div class="tp-note">Risk in: <span x-text="t.risk_score_input"></span> · Risk out: <span x-text="t.risk_score_output"></span> · Tokens: <span x-text="t.tokens_used||0"></span></div>
                        </div>
                        <div class="tp">
                            <div class="tp-lbl" style="color:var(--green);"><span class="tp-dot" style="background:var(--green);"></span>DEFENDER</div>
                            <div style="margin-bottom:.4rem;">
                                <span class="verdict-pill" :class="'v-'+(t.defender_verdict||'BLOCK')" x-text="t.defender_verdict||'BLOCK'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- Health Banner -->
<div class="health-banner">
    <span>Services</span>
    <template x-for="(svc, key) in health" :key="key">
        <div class="svc" :class="'svc-'+svc.status">
            <span class="svc-dot"></span>
            <span x-text="svc.name"></span>
            <span x-text="svc.status"></span>
        </div>
    </template>
    <div style="margin-left:auto;display:flex;gap:.5rem;align-items:center;">
        <span x-show="demoMode" class="demo-badge">DEMO MODE</span>
        <button class="btn-sm btn-sm-green" @click="refreshHealth()" style="font-size:.62rem;padding:.18rem .5rem;">↺ Refresh</button>
    </div>
</div>

<!-- Top Bar -->
<div class="topbar">
    <div class="brand">
        <div class="brand-icon">⚔</div>
        <div>
            <div class="brand-name">Red-Team <em>Arena</em></div>
            <div class="brand-sub">Enterprise AI Security Validation Console</div>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="tab-btn" :class="{active:tab==='arena'}" @click="tab='arena'">⚔ Arena</button>
        <button class="tab-btn" :class="{active:tab==='dashboard'}" @click="tab='dashboard';loadStats()">📊 Dashboard</button>
        <button class="tab-btn" :class="{active:tab==='history'}" @click="tab='history';loadHistory()">🗂 History</button>
        <button class="tab-btn" :class="{active:tab==='compare'}" @click="tab='compare'">⚖ Compare</button>
        <button class="tab-btn" :class="{active:tab==='scenarios'}" @click="tab='scenarios'">🧪 Scenarios</button>
        <button class="tab-btn" :class="{active:tab==='reports'}" @click="tab='reports';loadHistory()">📄 Reports</button>
    </div>

    <div class="topbar-right">
        <button class="btn-sm btn-sm-purple" @click="showOnboard=true">? Onboarding</button>
        <button class="btn-sm btn-sm-green" @click="seedDemo()" x-show="!demoMode">Load Demo</button>
        <button class="btn-sm btn-sm-red" @click="resetDemo()" x-show="demoMode">Clear Demo</button>
        <a href="/" class="btn-sm">← Landing</a>
    </div>
</div>

<div class="app-body">

    <!-- ═══════════════ ARENA TAB ═══════════════ -->
    <div class="tab-content" :class="{active:tab==='arena'}">
        <div class="arena-layout">

            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-hdr">
                    <span>Scenarios ({{ count($scenarios) }})</span>
                    <button class="btn-sm" style="font-size:.6rem;padding:.15rem .5rem;" @click="tab='scenarios'">+ New</button>
                </div>
                <div class="sidebar-stats">
                    <div class="sb-stat"><strong>{{ count($scenarios) }}</strong><span>Total</span></div>
                    <div class="sb-stat"><strong style="color:var(--red);">{{ $scenarios->where('metadata.severity','CRITICAL')->count() }}</strong><span>Critical</span></div>
                    <div class="sb-stat"><strong style="color:var(--blue);">OWASP</strong><span>Mapped</span></div>
                </div>
                @foreach($scenarios as $s)
                <div class="sc-card"
                     :class="{active: activeId==='{{ $s->id }}'}"
                     @click="selectScenario(@js(['id'=>$s->id,'category'=>$s->category,'description'=>$s->description,'severity'=>$s->metadata['severity']??'MEDIUM','patterns'=>$s->attack_patterns??[]]))">
                    <div class="sc-head">
                        <span class="cat-badge cat-{{ $s->category }}">{{ str_replace('_',' ',$s->category) }}</span>
                        <span class="sev-badge sev-{{ $s->metadata['severity']??'MEDIUM' }}">{{ $s->metadata['severity']??'N/A' }}</span>
                    </div>
                    <div class="sc-desc">{{ $s->description }}</div>
                    <div class="sc-chips">
                        @foreach(array_slice($s->attack_patterns??[],0,2) as $p)
                        <span class="chip">{{ str_replace('_',' ',$p) }}</span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </aside>

            <!-- Arena Main -->
            <main class="arena-main">
                <div class="arena-ctrl">
                    <div>
                        <div class="ctrl-title" x-text="activeId ? activeCat.replace(/_/g,' ').toUpperCase() : 'Security Validation Console'"></div>
                        <div class="ctrl-desc" x-text="activeId ? activeDesc : 'Select a scenario from the left panel to begin.'"></div>
                    </div>
                    <div class="ctrl-row">
                        <select class="ctrl-select" x-model="policyProfile">
                            <option value="strict">Strict Policy</option>
                            <option value="moderate">Moderate Policy</option>
                            <option value="permissive">Permissive Policy</option>
                        </select>
                        <select class="ctrl-select" x-model="provider" @change="updateModels()">
                            <option value="groq">Groq</option>
                            <option value="huggingface">Hugging Face</option>
                        </select>
                        <select class="ctrl-select" x-model="targetModel">
                            <template x-for="m in modelOptions" :key="m.value">
                                <option :value="m.value" x-text="m.label"></option>
                            </template>
                        </select>
                        <select class="ctrl-select" x-model="maxTurns">
                            <option value="2">2 Turns</option>
                            <option value="3" selected>3 Turns</option>
                            <option value="5">5 Turns</option>
                        </select>
                        <button class="btn-run" @click="runDuel()" :disabled="!activeId||running">
                            <span x-show="!running">⚡ Commence Attack</span>
                            <span x-show="running" class="loader" style="padding:0;gap:.4rem;"><span class="spin"></span> Running…</span>
                        </button>
                    </div>
                </div>

                <div class="arena-body">

                    <div class="kpi-strip">
                        <div class="kpi"><div class="lbl">Coverage</div><div class="val" style="color:var(--blue);font-size:.85rem;">OWASP LLM</div></div>
                        <div class="kpi"><div class="lbl">Mode</div><div class="val" style="font-size:.85rem;">Red vs Blue</div></div>
                        <div class="kpi"><div class="lbl">Guardrails</div><div class="val" style="font-size:.85rem;">2 Engines</div></div>
                        <div class="kpi"><div class="lbl">Evidence</div><div class="val" style="color:var(--green);font-size:.85rem;">Audit Trail</div></div>
                    </div>

                    <template x-if="activeId">
                        <div class="sel-panel">
                            <div>
                                <h3 x-text="activeCat.replace(/_/g,' ')+' — readiness assessment'"></h3>
                                <p x-text="activeDesc"></p>
                                <div class="sc-chips" style="margin-top:.55rem;">
                                    <span class="chip" x-text="activeSev+' severity'"></span>
                                    <template x-for="p in activePats" :key="p">
                                        <span class="chip" x-text="p.replace(/_/g,' ')"></span>
                                    </template>
                                </div>
                            </div>
                            <button class="btn-run" @click="runDuel()" :disabled="running">
                                <span x-show="!running">▶ Run</span>
                                <span x-show="running"><span class="spin"></span></span>
                            </button>
                        </div>
                    </template>

                    <template x-if="errorMessage">
                        <div class="error-box" x-text="errorMessage"></div>
                    </template>

                    <template x-if="!activeId && turns.length===0">
                        <div class="empty">
                            <div class="icon">▦</div>
                            <div style="font-weight:700;">Select an Assessment Scenario</div>
                            <div style="font-size:.78rem;max-width:400px;text-align:center;color:var(--muted);line-height:1.6;">Choose from the threat scenarios on the left to launch a controlled red-team adversarial simulation.</div>
                            <button class="btn-sm btn-sm-purple" @click="showOnboard=true" style="margin-top:.5rem;">View Onboarding Guide</button>
                        </div>
                    </template>

                    <!-- Summary -->
                    <template x-if="summary">
                        <div class="summary-card">
                            <div class="summary-title">
                                <span>✓ Assessment Complete</span>
                                <div style="display:flex;gap:.5rem;">
                                    <button class="btn-sm btn-sm-green" @click="openReport(lastDuelId)" style="font-size:.65rem;">📄 Export Report</button>
                                    <button class="btn-sm" @click="openTimeline(lastDuelId)" style="font-size:.65rem;">🔍 Timeline</button>
                                </div>
                            </div>
                            <div class="summary-grid">
                                <div class="stat-box"><div class="val" style="color:var(--red);" x-text="summary.red_team_wins"></div><div class="key">Red Wins</div></div>
                                <div class="stat-box"><div class="val" style="color:var(--blue);" x-text="summary.blue_team_wins"></div><div class="key">Blue Wins</div></div>
                                <div class="stat-box"><div class="val" style="color:var(--yellow);" x-text="summary.draws"></div><div class="key">Draws</div></div>
                                <div class="stat-box"><div class="val" :style="summary.attack_success_rate>0.5?'color:var(--red)':'color:var(--green)'" x-text="(summary.attack_success_rate*100).toFixed(0)+'%'"></div><div class="key">Attack Success</div></div>
                                <div class="stat-box"><div class="val" style="color:var(--green);" x-text="(summary.defense_effectiveness*100).toFixed(0)+'%'"></div><div class="key">Defense Eff.</div></div>
                                <div class="stat-box"><div class="val" style="font-size:.8rem;" x-text="(summary.owasp_categories_triggered||[]).join(', ')||'N/A'"></div><div class="key">OWASP</div></div>
                            </div>
                        </div>
                    </template>

                    <!-- Turn cards -->
                    <template x-for="t in turns" :key="t.turn">
                        <div class="turn-card">
                            <div class="turn-hdr">
                                <span>Turn <span x-text="t.turn"></span></span>
                                <div style="display:flex;gap:.4rem;align-items:center;">
                                    <span x-show="t.latency_ms" style="color:var(--muted);font-weight:400;" x-text="t.latency_ms+'ms'"></span>
                                    <span class="outcome-pill" :class="'o-'+(t.judge_outcome||'draw')" x-text="(t.judge_outcome||'pending').replace(/_/g,' ')"></span>
                                    <span x-show="t.owasp_category" style="font-size:.6rem;color:var(--muted);" x-text="t.owasp_category"></span>
                                </div>
                            </div>
                            <div class="turn-grid">
                                <div class="tp">
                                    <div class="tp-lbl" style="color:var(--red);"><span class="tp-dot" style="background:var(--red);"></span>ATTACKER</div>
                                    <div class="tech-pill" x-text="t.attacker_technique"></div>
                                    <div class="tp-text" x-text="t.adversarial_prompt"></div>
                                    <div class="tp-note" x-text="t.attacker_reasoning"></div>
                                </div>
                                <div class="tp">
                                    <div class="tp-lbl" style="color:var(--blue);"><span class="tp-dot" style="background:var(--blue);"></span>TARGET MODEL</div>
                                    <div class="tp-text" x-text="t.model_response"></div>
                                    <template x-if="t.guardrail_input_result && t.guardrail_input_result.risk_score>0">
                                        <div class="tp-note">Risk: <span x-text="t.guardrail_input_result.risk_score"></span>
                                            <span x-text="t.guardrail_input_result.scanners_triggered?.length ? ' · '+t.guardrail_input_result.scanners_triggered.join(', ') : ''"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="tp">
                                    <div class="tp-lbl" style="color:var(--green);"><span class="tp-dot" style="background:var(--green);"></span>DEFENDER</div>
                                    <span class="verdict-pill" :class="'v-'+(t.defender_verdict||'BLOCK')" x-text="t.defender_verdict||'BLOCK'"></span>
                                    <div class="tp-note" style="margin-top:.4rem;" x-text="t.defender_reasoning"></div>
                                    <template x-if="t.judge_outcome==='red_team_win'">
                                        <div class="rem-box">
                                            <div class="rem-title">⚠ Attack Succeeded — Recommendations</div>
                                            <ul class="rem-list">
                                                <li x-text="'Technique used: '+t.attacker_technique+' — add to block list'"></li>
                                                <li>Increase guardrail scanner sensitivity for this category</li>
                                                <li>Review system prompt for exploitable framing vectors</li>
                                                <li x-show="t.owasp_category" x-text="'Review '+t.owasp_category+' controls in your AI security policy'"></li>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="running">
                        <div class="loader"><span class="spin"></span> Agents are dueling… 10–30 seconds per turn.</div>
                    </template>

                </div>
            </main>
        </div>
    </div>

    <!-- ═══════════════ DASHBOARD TAB ═══════════════ -->
    <div class="tab-content" :class="{active:tab==='dashboard'}">
        <div class="dash-layout" style="width:100%;overflow-y:auto;padding:1.25rem;">

            <div x-show="statsLoading" class="loader" style="padding:2rem 0;justify-content:center;"><span class="spin"></span> Loading analytics…</div>

            <template x-if="!statsLoading && stats">
                <div>
                    <!-- KPI row -->
                    <div class="dash-kpis">
                        <div class="dash-kpi">
                            <div class="dk-val" x-text="stats.total_duels"></div>
                            <div class="dk-lbl">Total Duels</div>
                            <div class="dk-sub" x-text="stats.total_turns+' turns recorded'"></div>
                        </div>
                        <div class="dash-kpi">
                            <div class="dk-val" :style="stats.overall_attack_success>50?'color:var(--red)':'color:var(--green)'" x-text="stats.overall_attack_success+'%'"></div>
                            <div class="dk-lbl">Attack Success Rate</div>
                            <div class="dk-sub" x-text="stats.total_red_wins+' red team wins'"></div>
                        </div>
                        <div class="dash-kpi">
                            <div class="dk-val" style="color:var(--green);" x-text="stats.overall_defense_effectiveness+'%'"></div>
                            <div class="dk-lbl">Defense Effectiveness</div>
                            <div class="dk-sub" x-text="stats.total_blue_wins+' defenses held'"></div>
                        </div>
                        <div class="dash-kpi">
                            <div class="dk-val" style="color:var(--yellow);" x-text="stats.total_draws"></div>
                            <div class="dk-lbl">Draws / Inconclusive</div>
                            <div class="dk-sub">Needs further assessment</div>
                        </div>
                    </div>

                    <div class="dash-row">
                        <!-- Risk Heatmap -->
                        <div class="dash-card" style="grid-column:1/-1;">
                            <div class="dc-title">RISK HEATMAP — Attack Success Rate by Scenario × Policy Profile</div>
                            <div class="heatmap-wrap" x-show="Object.keys(stats.heatmap||{}).length > 0">
                                <template x-if="stats.heatmap">
                                    <div>
                                        <!-- Header row -->
                                        <div :style="`display:grid;grid-template-columns:160px repeat(3,1fr);gap:4px;margin-bottom:4px;`">
                                            <div></div>
                                            <div class="hm-header">Strict</div>
                                            <div class="hm-header">Moderate</div>
                                            <div class="hm-header">Permissive</div>
                                        </div>
                                        <!-- Data rows -->
                                        <template x-for="(row, cat) in stats.heatmap" :key="cat">
                                            <div :style="`display:grid;grid-template-columns:160px repeat(3,1fr);gap:4px;margin-bottom:4px;`">
                                                <div class="hm-row-label" x-text="cat.replace(/_/g,' ')"></div>
                                                <template x-for="policy in ['strict','moderate','permissive']" :key="policy">
                                                    <div class="hm-cell"
                                                         :class="row[policy]===null ? 'hm-empty' : ''"
                                                         :style="row[policy]!==null ? `background:${heatColor(row[policy])};color:${row[policy]>60?'#fff':'#111'};` : ''"
                                                         x-text="row[policy]!==null ? row[policy]+'%' : '—'">
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <div x-show="!stats.heatmap||Object.keys(stats.heatmap||{}).length===0" style="color:var(--muted);font-size:.78rem;padding:1rem 0;">Run duels across multiple scenarios and policies to populate the heatmap.</div>
                        </div>
                    </div>

                    <div class="dash-row">
                        <!-- Technique breakdown -->
                        <div class="dash-card">
                            <div class="dc-title">TECHNIQUE SUCCESS RATES</div>
                            <template x-if="Object.keys(stats.technique_breakdown||{}).length > 0">
                                <div>
                                    <template x-for="(v, tech) in stats.technique_breakdown" :key="tech">
                                        <div class="bar-item">
                                            <div class="bar-label" x-text="tech.replace(/_/g,' ')"></div>
                                            <div class="bar-track">
                                                <div class="bar-fill" :style="`width:${v.success_rate}%;background:${v.success_rate>60?'var(--red)':v.success_rate>30?'var(--orange)':'var(--green)'}`"></div>
                                            </div>
                                            <div class="bar-val" x-text="v.success_rate+'%'"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div x-show="!stats.technique_breakdown||Object.keys(stats.technique_breakdown||{}).length===0" style="color:var(--muted);font-size:.78rem;">No technique data yet.</div>
                        </div>

                        <!-- OWASP distribution -->
                        <div class="dash-card">
                            <div class="dc-title">OWASP CATEGORY DISTRIBUTION</div>
                            <template x-if="Object.keys(stats.owasp_distribution||{}).length > 0">
                                <div>
                                    <template x-for="(count, cat) in stats.owasp_distribution" :key="cat">
                                        <div class="bar-item">
                                            <div class="bar-label" x-text="cat"></div>
                                            <div class="bar-track">
                                                <div class="bar-fill" style="background:var(--red);" :style="`width:${Math.min(100, count * 15)}%;background:var(--red);`"></div>
                                            </div>
                                            <div class="bar-val" x-text="count+' turns'"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div x-show="!stats.owasp_distribution||Object.keys(stats.owasp_distribution||{}).length===0" style="color:var(--muted);font-size:.78rem;">No OWASP data yet.</div>
                        </div>
                    </div>

                    <!-- Model comparison table -->
                    <div class="dash-card dash-card-full">
                        <div class="dc-title">MODEL VULNERABILITY COMPARISON</div>
                        <table class="tbl" x-show="Object.keys(stats.model_comparison||{}).length > 0">
                            <thead><tr><th>Model</th><th>Duels</th><th>Avg Attack Success</th><th>Red Wins</th><th>Blue Wins</th></tr></thead>
                            <tbody>
                                <template x-for="(v, model) in stats.model_comparison" :key="model">
                                    <tr>
                                        <td style="font-family:var(--mono);" x-text="model"></td>
                                        <td x-text="v.duels"></td>
                                        <td><span :style="v.avg_attack_success>50?'color:var(--red)':'color:var(--green)'" x-text="v.avg_attack_success+'%'"></span></td>
                                        <td style="color:var(--red);" x-text="v.total_red_wins"></td>
                                        <td style="color:var(--blue);" x-text="v.total_blue_wins"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="!stats.model_comparison||Object.keys(stats.model_comparison||{}).length===0" style="color:var(--muted);font-size:.78rem;">Run duels with different models to compare vulnerability profiles.</div>
                    </div>
                </div>
            </template>

            <template x-if="!statsLoading && !stats">
                <div class="empty" style="height:50vh;">
                    <div class="icon">📊</div>
                    <div style="font-weight:700;">No Analytics Yet</div>
                    <div style="font-size:.78rem;color:var(--muted);">Run your first duel from the Arena tab, or load demo data.</div>
                    <button class="btn-sm btn-sm-purple" @click="seedDemo()" style="margin-top:.5rem;">Load Demo Data</button>
                </div>
            </template>
        </div>
    </div>

    <!-- ═══════════════ HISTORY TAB ═══════════════ -->
    <div class="tab-content" :class="{active:tab==='history'}">
        <div class="history-layout">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem;">
                <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);">Assessment History</div>
                <div style="display:flex;gap:.5rem;">
                    <button class="btn-sm" @click="loadHistory()">↺ Refresh</button>
                </div>
            </div>
            <div x-show="historyLoading" class="loader"><span class="spin"></span> Loading history…</div>
            <template x-if="!historyLoading && historyDuels.length === 0">
                <div class="empty" style="height:40vh;">
                    <div class="icon">🗂</div>
                    <div style="font-weight:700;">No Duel History</div>
                    <div style="font-size:.78rem;color:var(--muted);">Completed assessments will appear here.</div>
                </div>
            </template>
            <template x-if="historyDuels.length > 0">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Duel ID</th><th>Scenario</th><th>Model</th><th>Policy</th>
                            <th>Turns</th><th>Red</th><th>Blue</th><th>ASR</th><th>Date</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="d in historyDuels" :key="d.duel_id">
                            <tr>
                                <td style="font-family:var(--mono);font-size:.65rem;" x-text="d.duel_id.substring(0,8)+'…'"></td>
                                <td><span class="cat-badge" :class="'cat-'+d.scenario" x-text="(d.scenario||'').replace(/_/g,' ')"></span></td>
                                <td style="font-family:var(--mono);font-size:.68rem;" x-text="d.target_model"></td>
                                <td><span class="chip" x-text="d.policy_profile"></span></td>
                                <td x-text="d.total_turns"></td>
                                <td style="color:var(--red);" x-text="d.red_team_wins"></td>
                                <td style="color:var(--blue);" x-text="d.blue_team_wins"></td>
                                <td :style="parseFloat(d.attack_success)>50?'color:var(--red)':'color:var(--green)'" x-text="d.attack_success+'%'"></td>
                                <td style="font-size:.68rem;" x-text="d.created_at"></td>
                                <td>
                                    <div class="tbl-actions">
                                        <button class="btn-sm" style="font-size:.62rem;" @click="openTimeline(d.duel_id)">Timeline</button>
                                        <button class="btn-sm btn-sm-green" style="font-size:.62rem;" @click="openReport(d.duel_id)">Report</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>
    </div>

    <!-- ═══════════════ COMPARE TAB ═══════════════ -->
    <div class="tab-content" :class="{active:tab==='compare'}">
        <div class="compare-layout">
            <div class="cmp-ctrl">
                <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.75rem;">Model Comparison Setup</div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:.65rem;align-items:flex-end;">
                    <div class="form-group">
                        <label>Scenario</label>
                        <select class="form-control" x-model="cmpScenarioId">
                            <option value="">— select —</option>
                            @foreach($scenarios as $s)
                            <option value="{{ $s->id }}">{{ str_replace('_',' ',$s->category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model A</label>
                        <select class="form-control" x-model="cmpModel1">
                            <option value="llama3-8b-8192">llama3-8b (Groq)</option>
                            <option value="llama3-70b-8192">llama3-70b (Groq)</option>
                            <option value="mixtral-8x7b-32768">Mixtral 8x7B (Groq)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model B</label>
                        <select class="form-control" x-model="cmpModel2">
                            <option value="llama3-70b-8192">llama3-70b (Groq)</option>
                            <option value="llama3-8b-8192">llama3-8b (Groq)</option>
                            <option value="mixtral-8x7b-32768">Mixtral 8x7B (Groq)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Policy</label>
                        <select class="form-control" x-model="cmpPolicy">
                            <option value="strict">Strict</option>
                            <option value="moderate">Moderate</option>
                            <option value="permissive">Permissive</option>
                        </select>
                    </div>
                    <button class="btn-run" @click="runCompare()" :disabled="!cmpScenarioId||cmpRunning" style="height:2.1rem;">
                        <span x-show="!cmpRunning">⚖ Compare</span>
                        <span x-show="cmpRunning" class="loader" style="padding:0;gap:.4rem;"><span class="spin"></span></span>
                    </button>
                </div>
            </div>

            <template x-if="cmpError">
                <div class="error-box" x-text="cmpError"></div>
            </template>

            <template x-if="cmpResults">
                <div>
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.75rem;display:flex;align-items:center;gap:.75rem;">
                        Comparison Results
                        <span class="winner-badge" x-text="'Winner (most resilient): '+cmpWinner"></span>
                    </div>
                    <div class="cmp-grid">
                        <template x-for="(result, modelId) in cmpResults" :key="modelId">
                            <div class="cmp-col">
                                <div class="cmp-col-title">
                                    <span x-text="modelId"></span>
                                    <span x-show="modelId===cmpWinner" class="winner-badge">Most Resilient ✓</span>
                                </div>
                                <template x-if="result.summary">
                                    <div>
                                        <div class="summary-grid">
                                            <div class="stat-box"><div class="val" style="color:var(--red);" x-text="result.summary.red_team_wins"></div><div class="key">Red Wins</div></div>
                                            <div class="stat-box"><div class="val" style="color:var(--blue);" x-text="result.summary.blue_team_wins"></div><div class="key">Blue Wins</div></div>
                                            <div class="stat-box"><div class="val" x-text="(result.summary.attack_success_rate*100).toFixed(0)+'%'"></div><div class="key">ASR</div></div>
                                            <div class="stat-box"><div class="val" style="color:var(--green);" x-text="(result.summary.defense_effectiveness*100).toFixed(0)+'%'"></div><div class="key">Def. Eff.</div></div>
                                        </div>
                                        <button class="btn-sm btn-sm-green" style="margin-top:.75rem;font-size:.65rem;" @click="openReport(result.duel_id)">Export Report</button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="!cmpResults && !cmpRunning">
                <div class="empty" style="height:40vh;">
                    <div class="icon">⚖</div>
                    <div style="font-weight:700;">Side-by-Side Model Testing</div>
                    <div style="font-size:.78rem;color:var(--muted);">Select a scenario and two models, then run to compare vulnerability profiles.</div>
                </div>
            </template>
        </div>
    </div>

    <!-- ═══════════════ SCENARIOS TAB ═══════════════ -->
    <div class="tab-content" :class="{active:tab==='scenarios'}">
        <div class="scenarios-layout">
            <!-- Builder -->
            <div class="sc-builder">
                <div class="sc-builder-title">
                    <span>🧪</span>
                    <span>Build a Custom Scenario</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" class="form-control" x-model="newScenario.category" placeholder="e.g. financial_fraud">
                    </div>
                    <div class="form-group">
                        <label>Severity</label>
                        <select class="form-control" x-model="newScenario.severity">
                            <option>CRITICAL</option><option>HIGH</option><option>MEDIUM</option><option>LOW</option>
                        </select>
                    </div>
                    <div class="form-group form-group-full">
                        <label>Description</label>
                        <input type="text" class="form-control" x-model="newScenario.description" placeholder="What threat scenario does this test?">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Target Model System Prompt</label>
                        <textarea class="form-control" x-model="newScenario.base_prompt" placeholder="You are a helpful assistant..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>OWASP Category</label>
                        <select class="form-control" x-model="newScenario.owasp">
                            <option value="LLM01">LLM01 — Prompt Injection</option>
                            <option value="LLM02">LLM02 — Insecure Output</option>
                            <option value="LLM06">LLM06 — Sensitive Info Disclosure</option>
                            <option value="LLM08">LLM08 — Excessive Agency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Attack Patterns</label>
                        <div class="patterns-grid">
                            <template x-for="p in allPatterns" :key="p">
                                <label class="pattern-chk">
                                    <input type="checkbox" :value="p" x-model="newScenario.attack_patterns">
                                    <span x-text="p.replace(/_/g,' ')"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
                <div style="margin-top:.9rem;display:flex;gap:.6rem;align-items:center;">
                    <button class="btn-run" @click="createScenario()" :disabled="scenarioSaving">
                        <span x-show="!scenarioSaving">✚ Create Scenario</span>
                        <span x-show="scenarioSaving"><span class="spin"></span> Saving…</span>
                    </button>
                    <span x-show="scenarioSuccess" style="color:var(--green);font-size:.75rem;font-weight:600;">✓ Scenario created!</span>
                    <span x-show="scenarioError" style="color:var(--red);font-size:.75rem;" x-text="scenarioError"></span>
                </div>
            </div>

            <!-- Scenario list -->
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.75rem;">All Scenarios ({{ count($scenarios) }})</div>
            <div class="sc-list-grid">
                @foreach($scenarios as $s)
                <div class="sc-list-card">
                    @if(!empty($s->metadata['custom']))
                    <span class="custom-tag">CUSTOM</span>
                    @endif
                    <div class="sc-head" style="margin-bottom:.4rem;">
                        <span class="cat-badge cat-{{ $s->category }}">{{ str_replace('_',' ',$s->category) }}</span>
                        <span class="sev-badge sev-{{ $s->metadata['severity']??'MEDIUM' }}">{{ $s->metadata['severity']??'N/A' }}</span>
                    </div>
                    <div class="sc-desc" style="margin-bottom:.5rem;">{{ $s->description }}</div>
                    <div class="sc-chips">
                        @foreach($s->attack_patterns??[] as $p)
                        <span class="chip">{{ str_replace('_',' ',$p) }}</span>
                        @endforeach
                    </div>
                    <div style="margin-top:.65rem;display:flex;gap:.4rem;">
                        <button class="btn-sm" @click="tab='arena';selectScenario(@js(['id'=>$s->id,'category'=>$s->category,'description'=>$s->description,'severity'=>$s->metadata['severity']??'MEDIUM','patterns'=>$s->attack_patterns??[]]))">Use in Arena</button>
                        @if(!empty($s->metadata['custom']))
                        <button class="btn-sm btn-sm-red" @click="deleteScenario('{{ $s->id }}')">Delete</button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ═══════════════ REPORTS TAB ═══════════════ -->
    <div class="tab-content" :class="{active:tab==='reports'}">
        <div class="reports-layout">
            <div class="report-select">
                <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.75rem;">Generate Executive Report</div>
                <div style="display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:flex-end;">
                    <div class="form-group">
                        <label>Select Assessment</label>
                        <select class="form-control" x-model="reportDuelId">
                            <option value="">— select a completed duel —</option>
                            <template x-for="d in historyDuels" :key="d.duel_id">
                                <option :value="d.duel_id" x-text="`${(d.scenario||'unknown').replace(/_/g,' ')} · ${d.target_model} · ${d.policy_profile} · ${d.created_at}`"></option>
                            </template>
                        </select>
                    </div>
                    <button class="btn-run" @click="openReport(reportDuelId)" :disabled="!reportDuelId">📄 Open Report</button>
                </div>
            </div>

            <template x-if="reportDuelId">
                <div>
                    <template x-for="d in historyDuels.filter(x=>x.duel_id===reportDuelId)" :key="d.duel_id">
                        <div class="report-preview">
                            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.75rem;">Report Preview</div>
                            <div class="rp-row"><div class="rp-label">Assessment ID</div><div class="rp-val" style="font-family:var(--mono);font-size:.72rem;" x-text="d.duel_id"></div></div>
                            <div class="rp-row"><div class="rp-label">Scenario</div><div class="rp-val" x-text="(d.scenario||'').replace(/_/g,' ')"></div></div>
                            <div class="rp-row"><div class="rp-label">Target Model</div><div class="rp-val" x-text="d.target_model"></div></div>
                            <div class="rp-row"><div class="rp-label">Policy Profile</div><div class="rp-val" x-text="d.policy_profile"></div></div>
                            <div class="rp-row"><div class="rp-label">Attack Success Rate</div><div class="rp-val" :style="parseFloat(d.attack_success)>50?'color:var(--red)':'color:var(--green)'" x-text="d.attack_success+'%'"></div></div>
                            <div class="rp-row"><div class="rp-label">Total Turns</div><div class="rp-val" x-text="d.total_turns"></div></div>
                            <div class="rp-row"><div class="rp-label">Date</div><div class="rp-val" x-text="d.created_at"></div></div>
                            <div style="margin-top:.85rem;padding:.75rem;background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.15);border-radius:.4rem;font-size:.72rem;color:var(--muted2);">
                                Report includes: Executive summary · KPI metrics · Full compliance evidence timeline · Prioritized remediation recommendations · OWASP mapping
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!reportDuelId">
                <div class="empty" style="height:40vh;">
                    <div class="icon">📄</div>
                    <div style="font-weight:700;">Executive Report Export</div>
                    <div style="font-size:.78rem;color:var(--muted);">Select a completed assessment above to generate a print-ready executive report.</div>
                </div>
            </template>
        </div>
    </div>

</div><!-- end app-body -->

<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;

function arenaApp() {
    return {
        tab: 'arena',
        showOnboard: !localStorage.getItem('rta_onboarded'),
        demoMode: false,

        // Health
        health: {
            nemo_guardrails: { name:'NeMo Guardrails', status:'checking' },
            llm_guard:       { name:'LLM Guard',       status:'checking' },
            groq:            { name:'Groq AI',          status:'checking' },
            huggingface:     { name:'Hugging Face',     status:'checking' },
            database:        { name:'Database',         status:'checking' },
        },

        // Arena
        scenarios: @json($scenarios),
        activeId: null, activeDesc:'', activeCat:'', activeSev:'', activePats:[],
        policyProfile: 'strict',
        provider: 'groq',
        targetModel: 'llama3-8b-8192',
        maxTurns: 3,
        running: false,
        turns: [], summary: null, errorMessage: '', lastDuelId: null,
        modelOptions: [
            {value:'llama3-8b-8192',   label:'llama3-8b'},
            {value:'llama3-70b-8192',  label:'llama3-70b'},
            {value:'mixtral-8x7b-32768',label:'Mixtral 8x7B'},
        ],

        // Dashboard
        stats: null, statsLoading: false,

        // History
        historyDuels: [], historyLoading: false,

        // Compare
        cmpScenarioId:'', cmpModel1:'llama3-8b-8192', cmpModel2:'llama3-70b-8192',
        cmpPolicy:'strict', cmpRunning:false, cmpResults:null, cmpWinner:'', cmpError:'',

        // Scenarios
        newScenario: { category:'', description:'', base_prompt:'', severity:'HIGH', owasp:'LLM01', attack_patterns:[] },
        allPatterns: ['direct_jailbreak','role_play_framing','multi_turn_escalation','obfuscation','translation_bypass','indirect_injection','hypothetical_framing','authority_impersonation','token_smuggling','many_shot_priming'],
        scenarioSaving: false, scenarioSuccess: false, scenarioError: '',

        // Reports
        reportDuelId: '',

        // Timeline
        timelineOpen: false, timelineTurns: [], timelineLoading: false,

        init() {
            this.refreshHealth();
            if (this.showOnboard) {
                // keep showing
            }
        },

        startDemo() {
            this.showOnboard = false;
            localStorage.setItem('rta_onboarded','1');
            this.seedDemo();
        },

        async refreshHealth() {
            try {
                const r = await fetch('/api/health');
                const d = await r.json();
                if (d.services) this.health = d.services;
            } catch(e) {
                Object.keys(this.health).forEach(k => this.health[k] = {...this.health[k], status:'offline'});
            }
        },

        selectScenario(s) {
            this.activeId   = s.id;
            this.activeDesc = s.description;
            this.activeCat  = s.category;
            this.activeSev  = s.severity;
            this.activePats = s.patterns || [];
            this.turns = []; this.summary = null; this.errorMessage = ''; this.lastDuelId = null;
        },

        updateModels() {
            if (this.provider === 'huggingface') {
                this.modelOptions = [
                    {value:'mistralai/Mistral-7B-Instruct-v0.2', label:'Mistral 7B Instruct'},
                    {value:'meta-llama/Meta-Llama-3-8B-Instruct', label:'Llama 3 8B Instruct'},
                    {value:'HuggingFaceH4/zephyr-7b-beta', label:'Zephyr 7B Beta'},
                ];
                this.targetModel = this.modelOptions[0].value;
            } else {
                this.modelOptions = [
                    {value:'llama3-8b-8192', label:'llama3-8b'},
                    {value:'llama3-70b-8192', label:'llama3-70b'},
                    {value:'mixtral-8x7b-32768', label:'Mixtral 8x7B'},
                ];
                this.targetModel = this.modelOptions[0].value;
            }
        },

        async runDuel() {
            if (!this.activeId || this.running) return;
            this.running = true; this.turns = []; this.summary = null; this.errorMessage = ''; this.lastDuelId = null;
            try {
                const r = await fetch(`/duels/${this.activeId}/run`, {
                    method:'POST',
                    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
                    body: JSON.stringify({ max_turns: this.maxTurns, policy_profile: this.policyProfile, target_model: this.targetModel, provider: this.provider })
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || 'Duel failed.');
                this.turns = d.turns || [];
                this.summary = d.summary || null;
                this.lastDuelId = d.duel_id || null;
            } catch(e) {
                this.errorMessage = e.message || 'Unable to run duel.';
            } finally {
                this.running = false;
            }
        },

        async loadStats() {
            if (this.stats) return;
            this.statsLoading = true;
            try {
                const r = await fetch('/api/stats');
                const d = await r.json();
                this.stats = d.total_duels > 0 ? d : null;
            } catch(e) {} finally { this.statsLoading = false; }
        },

        async loadHistory() {
            if (this.historyDuels.length > 0) return;
            this.historyLoading = true;
            try {
                const r = await fetch('/duels/history/all');
                const d = await r.json();
                this.historyDuels = d.duels || [];
            } catch(e) {} finally { this.historyLoading = false; }
        },

        async openTimeline(duelId) {
            if (!duelId) return;
            this.timelineOpen = true; this.timelineTurns = []; this.timelineLoading = true;
            try {
                const r = await fetch(`/duels/${duelId}/report`);
                const d = await r.json();
                this.timelineTurns = d.turns || [];
            } catch(e) {} finally { this.timelineLoading = false; }
        },

        openReport(duelId) {
            if (!duelId) return;
            window.open(`/duels/${duelId}/report/html`, '_blank');
        },

        async runCompare() {
            if (!this.cmpScenarioId || this.cmpRunning) return;
            this.cmpRunning = true; this.cmpResults = null; this.cmpError = ''; this.cmpWinner = '';
            try {
                const r = await fetch(`/duels/${this.cmpScenarioId}/compare`, {
                    method:'POST',
                    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
                    body: JSON.stringify({ models:[this.cmpModel1, this.cmpModel2], policy_profile:this.cmpPolicy, max_turns:3, provider:'groq' })
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || 'Compare failed.');
                this.cmpResults = d.comparison;
                this.cmpWinner  = d.winner || '';
                // Force history reload
                this.historyDuels = [];
            } catch(e) {
                this.cmpError = e.message;
            } finally { this.cmpRunning = false; }
        },

        async createScenario() {
            this.scenarioSaving = true; this.scenarioSuccess = false; this.scenarioError = '';
            try {
                const r = await fetch('/scenarios', {
                    method:'POST',
                    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
                    body: JSON.stringify({ ...this.newScenario, owasp_category: this.newScenario.owasp })
                });
                const d = await r.json();
                if (!r.ok) throw new Error(Object.values(d.errors||{}).flat().join(', ') || d.message || 'Error');
                this.scenarioSuccess = true;
                this.newScenario = { category:'', description:'', base_prompt:'', severity:'HIGH', owasp:'LLM01', attack_patterns:[] };
                setTimeout(()=>{ this.scenarioSuccess=false; window.location.reload(); }, 1500);
            } catch(e) {
                this.scenarioError = e.message;
            } finally { this.scenarioSaving = false; }
        },

        async deleteScenario(id) {
            if (!confirm('Delete this custom scenario?')) return;
            await fetch(`/scenarios/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'} });
            window.location.reload();
        },

        async seedDemo() {
            try {
                const r = await fetch('/demo/seed', { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'} });
                const d = await r.json();
                this.demoMode = true;
                this.stats = null; this.historyDuels = [];
                if (this.tab === 'dashboard') this.loadStats();
                if (this.tab === 'history') this.loadHistory();
            } catch(e) {}
        },

        async resetDemo() {
            if (!confirm('Clear all demo data?')) return;
            await fetch('/demo/reset', { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'} });
            this.demoMode = false; this.stats = null; this.historyDuels = [];
        },

        heatColor(val) {
            if (val === null) return 'rgba(255,255,255,0.04)';
            const v = Math.max(0, Math.min(100, val));
            if (v < 20) return `rgba(34,197,94,${0.2 + v/100})`;
            if (v < 50) return `rgba(234,179,8,${0.2 + v/100})`;
            return `rgba(239,68,68,${0.25 + v/200})`;
        },
    };
}
</script>
</body>
</html>
