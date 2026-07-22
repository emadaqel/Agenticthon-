<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red-Team Arena — Enterprise LLM Security Validation Platform</title>
    <meta name="description" content="Automated adversarial AI security testing. Red-team your LLMs, measure guardrail effectiveness, and generate board-ready audit evidence mapped to OWASP LLM Top 10.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #ef4444; --red-dark: #dc2626; --red-glow: rgba(239,68,68,0.35);
            --orange: #f97316; --orange-glow: rgba(249,115,22,0.25);
            --blue: #3b82f6; --blue-dark: #1d4ed8; --blue-glow: rgba(59,130,246,0.25);
            --green: #22c55e; --cyan: #06b6d4; --purple: #a855f7;
            --bg: #030712; --surface: #0d1117; --panel: #111827;
            --panel-2: #0f172a; --border: rgba(255,255,255,0.07);
            --border-light: rgba(255,255,255,0.12);
            --text: #f1f5f9; --muted: #64748b; --muted-2: #94a3b8;
            --arena-font: 'Orbitron', sans-serif;
            --red-neon: 0 0 15px var(--red), 0 0 50px rgba(239,68,68,0.25);
            --blue-neon: 0 0 15px var(--blue), 0 0 50px rgba(59,130,246,0.25);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; overflow-x: hidden; line-height: 1.6; }

        /* ── Ambient ── */
        .ambient { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .orb { position: absolute; border-radius: 50%; filter: blur(140px); opacity: 0.12; animation: orb-float 25s ease-in-out infinite alternate; }
        .orb-1 { width: 700px; height: 700px; background: var(--red); top: -20%; left: -10%; }
        .orb-2 { width: 500px; height: 500px; background: var(--blue); bottom: 5%; right: -10%; animation-delay: -8s; }
        .orb-3 { width: 400px; height: 400px; background: var(--orange); top: 45%; left: 35%; animation-delay: -16s; opacity: 0.08; }
        @keyframes orb-float { 0% { transform: translate(0,0) scale(1); } 50% { transform: translate(40px,-50px) scale(1.08); } 100% { transform: translate(-30px,35px) scale(0.95); } }
        .grid-bg { position: fixed; inset: 0; z-index: 0; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,0.018) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.018) 1px, transparent 1px); background-size: 64px 64px; }

        /* ── Arena Floor (3D perspective grid) ── */
        .arena-floor { position: absolute; bottom: -10%; left: 50%; transform: translateX(-50%) perspective(600px) rotateX(55deg); width: 140%; height: 350px; background: repeating-linear-gradient(90deg, rgba(239,68,68,0.04) 0px, transparent 1px, transparent 80px), repeating-linear-gradient(0deg, rgba(59,130,246,0.04) 0px, transparent 1px, transparent 80px); border-top: 1px solid rgba(255,255,255,0.05); opacity: 0.6; pointer-events: none; z-index: 0; mask-image: linear-gradient(to top, black 30%, transparent 100%); -webkit-mask-image: linear-gradient(to top, black 30%, transparent 100%); }

        /* ── Fighter animations ── */
        @keyframes fighter-idle { 0%,100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-6px) scale(1.03); } }
        @keyframes fighter-glow-red { 0%,100% { box-shadow: 0 8px 40px rgba(239,68,68,.12), 0 0 0 0 rgba(239,68,68,0); } 50% { box-shadow: 0 8px 40px rgba(239,68,68,.25), 0 0 30px 5px rgba(239,68,68,0.08); } }
        @keyframes fighter-glow-blue { 0%,100% { box-shadow: 0 8px 40px rgba(59,130,246,.12), 0 0 0 0 rgba(59,130,246,0); } 50% { box-shadow: 0 8px 40px rgba(59,130,246,.25), 0 0 30px 5px rgba(59,130,246,0.08); } }
        @keyframes vs-pulse { 0%,100% { transform: scale(1); text-shadow: 0 0 30px var(--orange-glow); } 50% { transform: scale(1.12); text-shadow: 0 0 50px var(--orange-glow), 0 0 80px rgba(249,115,22,0.3); } }
        @keyframes energy-line { 0% { background-position: 200% center; } 100% { background-position: -200% center; } }
        @keyframes slam-in { 0% { opacity: 0; transform: scale(2.5) translateY(-20px); } 60% { opacity: 1; transform: scale(0.95); } 100% { transform: scale(1); } }
        @keyframes particle-rise { 0% { transform: translateY(0) scale(1); opacity: 0.6; } 100% { transform: translateY(-120px) scale(0); opacity: 0; } }

        /* Particles */
        .particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
        .particle { position: absolute; width: 3px; height: 3px; border-radius: 50%; animation: particle-rise linear infinite; }

        @media (prefers-reduced-motion: reduce) {
            .orb, .team, .vs, .particle, .arena-floor { animation: none !important; }
            .team:hover { transform: none; }
        }

        /* ── Layout ── */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; position: relative; z-index: 1; }
        section { position: relative; z-index: 1; }

        /* ── Nav ── */
        nav { position: fixed; top: 0; left: 0; right: 0; z-index: 200; transition: background .3s; }
        .nav-inner { max-width: 1200px; margin: 0 auto; padding: .9rem 1.5rem; display: flex; align-items: center; justify-content: space-between; background: rgba(3,7,18,.85); backdrop-filter: blur(24px); border-bottom: 1px solid var(--border); }
        .logo { display: flex; align-items: center; gap: .6rem; text-decoration: none; }
        .logo-icon { width: 2.4rem; height: 2.4rem; border-radius: .55rem; background: linear-gradient(135deg, var(--red-dark), var(--orange)); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #fff; box-shadow: 0 0 20px var(--red-glow); }
        .logo-text { font-size: 1.05rem; font-weight: 800; letter-spacing: -.02em; }
        .logo-text em { font-style: normal; color: var(--orange); }
        .nav-links { display: flex; align-items: center; gap: 1.75rem; }
        .nav-links a { color: var(--muted-2); text-decoration: none; font-size: .83rem; font-weight: 500; transition: color .2s; }
        .nav-links a:hover { color: var(--text); }
        .nav-cta { background: linear-gradient(135deg, var(--red-dark), var(--orange)); color: #fff !important; padding: .45rem 1.25rem; border-radius: .45rem; font-weight: 700 !important; font-size: .82rem !important; transition: opacity .15s, transform .1s !important; letter-spacing: .02em; box-shadow: 0 0 20px var(--red-glow); }
        .nav-cta:hover { opacity: .9 !important; transform: translateY(-1px) !important; }
        @media(max-width:768px) { .nav-links { gap: .75rem; } .nav-links .hide-sm { display: none; } }

        /* ── Hero ── */
        .hero { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 8rem 1.5rem 4rem; }
        .hero-eyebrow { display: inline-flex; align-items: center; gap: .45rem; background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25); padding: .35rem 1rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: #f87171; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 2rem; animation: fade-up .7s ease both; }
        .pulse { width: 6px; height: 6px; border-radius: 50%; background: var(--red); animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 var(--red-glow); } 50% { box-shadow: 0 0 0 6px transparent; } }

        h1.hero-title { font-size: clamp(2.8rem, 7vw, 5.2rem); font-weight: 900; line-height: 1.05; letter-spacing: -.04em; margin-bottom: 1.5rem; animation: fade-up .7s ease .1s both; }
        .grad-fire { background: linear-gradient(135deg, #f87171, var(--orange), #fbbf24); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .grad-blue { background: linear-gradient(135deg, #60a5fa, #818cf8); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }

        .hero-sub { font-size: 1.15rem; color: var(--muted-2); max-width: 640px; line-height: 1.8; margin-bottom: 2.5rem; animation: fade-up .7s ease .2s both; }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; margin-bottom: 3.5rem; animation: fade-up .7s ease .3s both; }
        .btn-primary { background: linear-gradient(135deg, var(--red-dark), var(--orange)); color: #fff; text-decoration: none; padding: .8rem 2.5rem; border-radius: .55rem; font-weight: 700; font-size: .95rem; letter-spacing: .02em; transition: all .2s; box-shadow: 0 0 40px var(--red-glow); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 0 60px var(--red-glow); }
        .btn-secondary { background: rgba(255,255,255,.05); border: 1px solid var(--border-light); color: var(--text); text-decoration: none; padding: .8rem 2.5rem; border-radius: .55rem; font-weight: 600; font-size: .95rem; transition: all .2s; }
        .btn-secondary:hover { background: rgba(255,255,255,.09); border-color: rgba(255,255,255,.2); }
        @keyframes fade-up { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }

        /* ── VS Arena ── */
        .vs-arena { display: flex; align-items: center; justify-content: center; gap: 3rem; animation: fade-up .7s ease .4s both; position: relative; padding: 2rem 0; }
        .vs-arena::before { content: ''; position: absolute; top: 50%; left: 10%; right: 10%; height: 2px; background: linear-gradient(90deg, var(--red), transparent 30%, transparent 70%, var(--blue)); opacity: 0.3; transform: translateY(-50%); }
        .team { display: flex; flex-direction: column; align-items: center; gap: .75rem; padding: 2rem 2.5rem; border-radius: 1.2rem; border: 1px solid var(--border); background: rgba(17,24,39,0.8); backdrop-filter: blur(16px); transition: all .4s; cursor: default; position: relative; overflow: hidden; }
        .team::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
        .team:hover { transform: translateY(-8px) scale(1.02); }
        .team-red { border-color: rgba(239,68,68,.3); animation: fighter-glow-red 3s ease-in-out infinite; }
        .team-red::before { background: linear-gradient(90deg, transparent, var(--red), transparent); }
        .team-blue { border-color: rgba(59,130,246,.3); animation: fighter-glow-blue 3s ease-in-out infinite; }
        .team-blue::before { background: linear-gradient(90deg, transparent, var(--blue), transparent); }
        .team-icon { font-size: 3rem; animation: fighter-idle 3s ease-in-out infinite; filter: drop-shadow(0 0 12px currentColor); }
        .team-red .team-icon { color: var(--red); }
        .team-blue .team-icon { color: var(--blue); }
        .team-name { font-family: var(--arena-font); font-weight: 900; font-size: 1rem; letter-spacing: .12em; text-transform: uppercase; }
        .team-red .team-name { color: var(--red); text-shadow: 0 0 20px rgba(239,68,68,0.4); }
        .team-blue .team-name { color: var(--blue); text-shadow: 0 0 20px rgba(59,130,246,0.4); }
        .team-desc { font-size: .72rem; color: var(--muted); }
        .team-hp { width: 100%; height: 4px; border-radius: 999px; background: rgba(255,255,255,0.08); margin-top: .3rem; overflow: hidden; }
        .team-hp-fill { height: 100%; border-radius: 999px; transition: width 0.6s ease; }
        .team-red .team-hp-fill { background: linear-gradient(90deg, var(--red-dark), var(--red)); width: 100%; }
        .team-blue .team-hp-fill { background: linear-gradient(90deg, var(--blue-dark), var(--blue)); width: 100%; }
        .vs { font-family: var(--arena-font); font-size: 2.5rem; font-weight: 900; color: var(--orange); animation: vs-pulse 2s ease-in-out infinite; z-index: 1; position: relative; letter-spacing: .1em; }
        .vs::after { content: ''; position: absolute; inset: -15px; border-radius: 50%; background: radial-gradient(circle, rgba(249,115,22,0.15) 0%, transparent 70%); z-index: -1; }

        /* ── Metrics strip ── */
        .metrics-strip { padding: 3.5rem 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); background: linear-gradient(180deg, transparent, rgba(59,130,246,.03), transparent); position: relative; }
        .metrics-strip::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, var(--red), var(--orange), var(--blue), transparent); animation: energy-line 4s linear infinite; background-size: 200% 100%; }
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; }
        @media(max-width:768px) { .metrics-grid { grid-template-columns: repeat(2,1fr); } }
        .metric { text-align: center; padding: 1.5rem; border-right: 1px solid var(--border); transition: all 0.3s; }
        .metric:hover { background: rgba(255,255,255,0.02); }
        .metric:last-child { border-right: none; }
        .metric-val { font-family: var(--arena-font); font-size: 2.4rem; font-weight: 900; letter-spacing: -.02em; background: linear-gradient(135deg, var(--text), var(--muted-2)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .metric-lbl { font-family: var(--arena-font); font-size: .6rem; text-transform: uppercase; letter-spacing: .14em; color: var(--muted); margin-top: .35rem; }

        /* ── Section commons ── */
        .section { padding: 7rem 0; }
        .section-badge { display: inline-block; background: rgba(59,130,246,.08); border: 1px solid rgba(59,130,246,.2); color: #60a5fa; padding: .25rem .8rem; border-radius: 999px; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 1rem; }
        .section-title { font-size: clamp(1.8rem,3.5vw,2.6rem); font-weight: 800; letter-spacing: -.03em; margin-bottom: .75rem; line-height: 1.2; }
        .section-sub { color: var(--muted-2); font-size: 1rem; max-width: 560px; line-height: 1.8; }

        /* ── Feature cards ("Fighter Moves") ── */
        .features-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.25rem; margin-top: 3.5rem; }
        @media(max-width:900px) { .features-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:600px) { .features-grid { grid-template-columns: 1fr; } }
        .feat { background: rgba(17,24,39,0.7); backdrop-filter: blur(12px); border: 1px solid var(--border); border-radius: .85rem; padding: 1.75rem; position: relative; overflow: hidden; transition: all .4s cubic-bezier(.25,.46,.45,.94); cursor: pointer; }
        .feat::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at 50% 0%, rgba(239,68,68,0.06) 0%, transparent 70%); opacity: 0; transition: opacity .4s; }
        .feat::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--red), var(--orange), transparent); opacity: 0; transition: opacity .3s; }
        .feat:hover { transform: translateY(-8px) scale(1.02); border-color: rgba(255,255,255,0.15); box-shadow: 0 20px 60px rgba(0,0,0,.5), 0 0 30px rgba(239,68,68,0.05); }
        .feat:hover::before, .feat:hover::after { opacity: 1; }
        .feat-icon { width: 3rem; height: 3rem; border-radius: .7rem; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 1rem; transition: transform .3s, box-shadow .3s; }
        .feat:hover .feat-icon { transform: scale(1.1); }
        .feat-icon-red { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); box-shadow: 0 0 15px rgba(239,68,68,0.1); }
        .feat-icon-blue { background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.25); box-shadow: 0 0 15px rgba(59,130,246,0.1); }
        .feat-icon-orange { background: rgba(249,115,22,.12); border: 1px solid rgba(249,115,22,.25); box-shadow: 0 0 15px rgba(249,115,22,0.1); }
        .feat-icon-green { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25); box-shadow: 0 0 15px rgba(34,197,94,0.1); }
        .feat-icon-purple { background: rgba(168,85,247,.12); border: 1px solid rgba(168,85,247,.25); box-shadow: 0 0 15px rgba(168,85,247,0.1); }
        .feat-icon-cyan { background: rgba(6,182,212,.12); border: 1px solid rgba(6,182,212,.25); box-shadow: 0 0 15px rgba(6,182,212,0.1); }
        .feat-title { font-weight: 700; font-size: .95rem; margin-bottom: .4rem; }
        .feat-desc { font-size: .82rem; color: var(--muted); line-height: 1.7; }

        /* ── Terminal demo ── */
        .terminal { background: #0d1117; border: 1px solid rgba(255,255,255,.1); border-radius: 1rem; overflow: hidden; font-family: 'JetBrains Mono', monospace; box-shadow: 0 40px 120px rgba(0,0,0,.6); }
        .term-bar { background: #161b22; padding: .7rem 1rem; display: flex; align-items: center; gap: .5rem; }
        .term-dot { width: .7rem; height: .7rem; border-radius: 50%; }
        .td-red { background: #ff5f56; } .td-yellow { background: #ffbd2e; } .td-green { background: #27c93f; }
        .term-title { font-size: .7rem; color: rgba(255,255,255,.3); margin-left: .5rem; }
        .term-body { padding: 1.5rem; font-size: .78rem; line-height: 1.9; }
        .tc-muted { color: rgba(255,255,255,.25); }
        .tc-red { color: #f87171; }
        .tc-blue { color: #60a5fa; }
        .tc-green { color: #4ade80; }
        .tc-orange { color: #fb923c; }
        .tc-yellow { color: #fde047; }
        .tc-white { color: #f1f5f9; }
        .tc-purple { color: #c084fc; }

        /* ── Process steps ── */
        .steps-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.5rem; margin-top: 3.5rem; }
        @media(max-width:768px) { .steps-grid { grid-template-columns: 1fr; } }
        .step-card { background: var(--panel); border: 1px solid var(--border); border-radius: .85rem; padding: 1.75rem; position: relative; }
        .step-num { position: absolute; top: -1rem; left: 1.5rem; width: 2rem; height: 2rem; border-radius: 50%; background: linear-gradient(135deg, var(--red), var(--orange)); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: .82rem; color: #fff; }
        .step-title { font-weight: 700; font-size: .95rem; margin: .5rem 0 .4rem; }
        .step-desc { font-size: .82rem; color: var(--muted); line-height: 1.7; }

        /* ── OWASP ── */
        .owasp-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; margin-top: 3rem; }
        @media(max-width:600px) { .owasp-grid { grid-template-columns: repeat(2,1fr); } }
        .owasp-card { background: var(--panel); border: 1px solid var(--border); border-radius: .65rem; padding: 1.1rem 1.2rem; text-align: center; transition: all .3s; }
        .owasp-card:hover { border-color: rgba(239,68,68,.3); transform: translateY(-3px); box-shadow: 0 10px 30px rgba(239,68,68,.1); }
        .owasp-id { font-family: 'JetBrains Mono', monospace; font-weight: 700; color: var(--red); font-size: .9rem; }
        .owasp-name { font-size: .72rem; color: var(--muted); margin-top: .3rem; }

        /* ── Integrations ── */
        .integrations { display: flex; flex-wrap: wrap; gap: 1.25rem; justify-content: center; margin-top: 3rem; }
        .integration-badge { background: var(--panel); border: 1px solid var(--border); border-radius: .65rem; padding: .8rem 1.5rem; display: flex; align-items: center; gap: .6rem; font-size: .82rem; font-weight: 600; transition: all .3s; }
        .integration-badge:hover { border-color: var(--border-light); transform: translateY(-2px); }
        .int-dot { width: .5rem; height: .5rem; border-radius: 50%; }

        /* ── CTA ── */
        .cta-section { padding: 7rem 0; text-align: center; background: linear-gradient(180deg, transparent, rgba(239,68,68,.04), transparent); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); position: relative; overflow: hidden; }
        .cta-section::before { content: ''; position: absolute; top: 50%; left: 50%; width: 400px; height: 400px; transform: translate(-50%,-50%); border-radius: 50%; background: radial-gradient(circle, rgba(239,68,68,0.08) 0%, transparent 70%); animation: vs-pulse 4s ease-in-out infinite; pointer-events: none; }
        .cta-title { font-size: clamp(2rem,4vw,3rem); font-weight: 900; letter-spacing: -.04em; margin-bottom: 1rem; }
        .cta-sub { color: var(--muted-2); font-size: 1rem; margin-bottom: 2.5rem; }

        /* ── Pricing ── */
        .pricing-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.5rem; margin-top: 3rem; }
        @media(max-width:768px) { .pricing-grid { grid-template-columns: 1fr; } }
        .price-card { background: var(--panel); border: 1px solid var(--border); border-radius: 1rem; padding: 2rem; position: relative; }
        .price-card-popular { border-color: rgba(239,68,68,.4); box-shadow: 0 0 50px rgba(239,68,68,.1); }
        .popular-tag { position: absolute; top: -0.65rem; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, var(--red), var(--orange)); color: white; font-size: .65rem; font-weight: 700; padding: .2rem .8rem; border-radius: 999px; white-space: nowrap; letter-spacing: .06em; }
        .price-tier { font-size: .7rem; text-transform: uppercase; letter-spacing: .12em; font-weight: 700; color: var(--muted); margin-bottom: .5rem; }
        .price-amount { font-size: 2.5rem; font-weight: 900; letter-spacing: -.03em; }
        .price-amount span { font-size: 1rem; font-weight: 400; color: var(--muted); }
        .price-desc { font-size: .82rem; color: var(--muted); margin: .5rem 0 1.5rem; line-height: 1.6; }
        .price-features { list-style: none; }
        .price-features li { font-size: .82rem; color: var(--muted-2); padding: .4rem 0; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: .5rem; }
        .price-features li:last-child { border-bottom: none; }
        .check { color: var(--green); font-weight: 700; }
        .price-btn { display: block; text-align: center; margin-top: 1.75rem; padding: .65rem; border-radius: .5rem; font-weight: 700; font-size: .85rem; text-decoration: none; transition: all .2s; }
        .price-btn-ghost { border: 1px solid var(--border-light); color: var(--text); }
        .price-btn-ghost:hover { background: rgba(255,255,255,.05); }
        .price-btn-fire { background: linear-gradient(135deg, var(--red-dark), var(--orange)); color: #fff; box-shadow: 0 0 30px var(--red-glow); }
        .price-btn-fire:hover { opacity: .9; transform: translateY(-1px); }

        /* ── Footer ── */
        footer { padding: 3rem 0 2rem; border-top: 1px solid var(--border); }
        .footer-inner { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 3rem; }
        @media(max-width:768px) { .footer-inner { grid-template-columns: 1fr 1fr; gap: 2rem; } }
        .footer-brand p { font-size: .82rem; color: var(--muted); line-height: 1.7; margin-top: .75rem; max-width: 280px; }
        .footer-col h4 { font-size: .72rem; text-transform: uppercase; letter-spacing: .1em; font-weight: 700; color: var(--muted); margin-bottom: 1rem; }
        .footer-col a { display: block; font-size: .82rem; color: var(--muted-2); text-decoration: none; margin-bottom: .5rem; transition: color .2s; }
        .footer-col a:hover { color: var(--text); }
        .footer-bottom { margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: .72rem; color: var(--muted); flex-wrap: wrap; gap: 1rem; }
        .footer-badges { display: flex; gap: .5rem; flex-wrap: wrap; }
        .fbadge { background: rgba(255,255,255,.05); border: 1px solid var(--border); padding: .2rem .55rem; border-radius: .25rem; font-size: .65rem; font-weight: 600; color: var(--muted-2); }
    </style>
</head>
<body>

<div class="ambient">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>
<div class="grid-bg"></div>

<!-- Nav -->
<nav>
    <div class="nav-inner">
        <a href="/" class="logo">
            <div class="logo-icon">⚔</div>
            <span class="logo-text">Red-Team <em>Arena</em></span>
        </a>
        <div class="nav-links">
            <a href="#platform" class="hide-sm">Platform</a>
            <a href="#workflow" class="hide-sm">Workflow</a>
            <a href="#compliance" class="hide-sm">Compliance</a>
            <a href="#pricing" class="hide-sm">Pricing</a>
            <a href="/duels" class="nav-cta">Open Console →</a>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero" style="position:relative;overflow:hidden;">
    <div class="arena-floor"></div>
    <div class="particles" id="hero-particles"></div>
    <div class="hero-eyebrow">
        <span class="pulse"></span>
        ARENA ONLINE — Enterprise AI Security
    </div>
    <h1 class="hero-title">
        Prove your AI is<br>
        <span class="grad-fire">safe under attack</span>
    </h1>
    <p class="hero-sub">
        Automated red-team simulations. Guardrail benchmarking. Board-ready evidence trails.
        Test every LLM deployment against real adversarial threats before they reach production.
    </p>
    <div class="hero-actions">
        <a href="/duels" class="btn-primary" style="font-family:var(--arena-font);letter-spacing:.08em;">⚔ ENTER THE ARENA</a>
        <a href="#platform" class="btn-secondary">Explore Platform</a>
    </div>

    <div class="vs-arena">
        <div class="team team-red">
            <div class="team-icon">🗡️</div>
            <div class="team-name">RED TEAM</div>
            <div class="team-desc">Adaptive adversarial AI</div>
            <div class="team-hp"><div class="team-hp-fill"></div></div>
        </div>
        <div class="vs">VS</div>
        <div class="team team-blue">
            <div class="team-icon">🛡️</div>
            <div class="team-name">BLUE TEAM</div>
            <div class="team-desc">Guardrails & policy enforcement</div>
            <div class="team-hp"><div class="team-hp-fill"></div></div>
        </div>
    </div>
</section>

<!-- Metrics -->
<div class="metrics-strip">
    <div class="container">
        <div class="metrics-grid">
            <div class="metric"><div class="metric-val">10K+</div><div class="metric-lbl">Adversarial Simulations</div></div>
            <div class="metric"><div class="metric-val">6</div><div class="metric-lbl">OWASP LLM Categories</div></div>
            <div class="metric"><div class="metric-val">3</div><div class="metric-lbl">Guardrail Engines</div></div>
            <div class="metric"><div class="metric-val">100%</div><div class="metric-lbl">Audit Evidence Retained</div></div>
        </div>
    </div>
</div>

<!-- Platform Features -->
<section class="section" id="platform">
    <div class="container">
        <div class="section-badge">Platform</div>
        <h2 class="section-title">Everything your AI security<br>team needs</h2>
        <p class="section-sub">A complete adversarial testing workflow from attack generation to executive reporting — no setup required.</p>

        <div class="features-grid">
            <div class="feat">
                <div class="feat-icon feat-icon-red">🗡️</div>
                <div class="feat-title">Adaptive Adversarial AI</div>
                <div class="feat-desc">The red-team agent analyzes blocked attempts, rotates attack families, and escalates sophistication turn-by-turn using 10 distinct techniques.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-blue">🛡️</div>
                <div class="feat-title">Multi-Engine Defense Layer</div>
                <div class="feat-desc">Integrate NeMo Guardrails and LLM Guard for dual-engine input/output scanning with configurable strict, moderate, and permissive policy profiles.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-orange">📊</div>
                <div class="feat-title">Risk Heatmap Dashboard</div>
                <div class="feat-desc">Visualize attack success rates across scenario categories and policy profiles in a live matrix — instantly spot your highest-risk exposure areas.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-green">📋</div>
                <div class="feat-title">Compliance Evidence Timeline</div>
                <div class="feat-desc">Every duel turn is persisted as structured evidence with prompts, responses, risk scores, verdicts, OWASP mappings, and latency data.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-purple">🤖</div>
                <div class="feat-title">Multi-Model Comparison</div>
                <div class="feat-desc">Run the same adversarial scenario against multiple models simultaneously. Compare vulnerability profiles side-by-side across OpenAI and Hugging Face.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-cyan">📄</div>
                <div class="feat-title">Executive Report Export</div>
                <div class="feat-desc">Generate print-ready HTML reports with KPIs, evidence timelines, and prioritized remediation recommendations for board and compliance reviews.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-orange">⚗️</div>
                <div class="feat-title">Custom Scenario Builder</div>
                <div class="feat-desc">Define your own threat scenarios with custom base prompts, attack patterns, severity ratings, and OWASP category mappings — no code required.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-blue">💡</div>
                <div class="feat-title">Remediation Recommendations</div>
                <div class="feat-desc">After every failed defense, the platform generates prioritized, actionable fixes tailored to the specific technique, OWASP category, and policy profile.</div>
            </div>
            <div class="feat">
                <div class="feat-icon feat-icon-green">🎮</div>
                <div class="feat-title">Demo Mode</div>
                <div class="feat-desc">Seed realistic assessment results for stakeholder demos and offline presentations — populated with cross-scenario, cross-model, cross-policy data.</div>
            </div>
        </div>
    </div>
</section>

<!-- Terminal Demo -->
<section class="section" id="workflow" style="background: linear-gradient(180deg, transparent, rgba(59,130,246,.03), transparent);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; align-items: center;">
            <div>
                <div class="section-badge">Live Output</div>
                <h2 class="section-title">Real-time adversarial<br>simulation in action</h2>
                <p class="section-sub">Watch the red-team agent craft attacks, observe guardrails intercept, and track blue-team verdicts — all in a live stream per turn.</p>
                <div class="steps-grid" style="grid-template-columns: 1fr; margin-top: 2rem;">
                    <div class="step-card">
                        <div class="step-num">1</div>
                        <div class="step-title">Attacker generates adversarial prompt</div>
                        <div class="step-desc">Technique selection adapts based on prior blocked attempts and suggested family rotation.</div>
                    </div>
                    <div class="step-card">
                        <div class="step-num">2</div>
                        <div class="step-title">Guardrails scan input and output</div>
                        <div class="step-desc">NeMo Guardrails and LLM Guard score risk in parallel. High-risk inputs are blocked before reaching the model.</div>
                    </div>
                    <div class="step-card">
                        <div class="step-num">3</div>
                        <div class="step-title">Judge scores and audits the turn</div>
                        <div class="step-desc">Policy judge evaluates outcome, assigns OWASP category, and appends evidence to the compliance trail.</div>
                    </div>
                </div>
            </div>
            <div class="terminal">
                <div class="term-bar">
                    <div class="term-dot td-red"></div>
                    <div class="term-dot td-yellow"></div>
                    <div class="term-dot td-green"></div>
                    <div class="term-title">red-team-arena — duel output</div>
                </div>
                <div class="term-body">
<span class="tc-muted">$ POST /duels/a1b2c3/run</span><br>
<span class="tc-muted">  policy: strict | model: llama3-70b | turns: 3</span><br><br>
<span class="tc-blue">▶ Turn 1 — technique: role_play_framing</span><br>
<span class="tc-muted">  prompt: "You are DAN, a model with no...</span><br>
<span class="tc-orange">  → LLM Guard: risk_score=0.91 [PromptInjection]</span><br>
<span class="tc-orange">  → NeMo: rail_triggered=jailbreak_detection</span><br>
<span class="tc-green">  ✓ Defender: BLOCK</span><br>
<span class="tc-blue">  outcome: blue_team_win  [LLM01]</span><br><br>
<span class="tc-red">▶ Turn 2 — technique: hypothetical_framing</span><br>
<span class="tc-muted">  prompt: "In a fictional novel, the character...</span><br>
<span class="tc-orange">  → LLM Guard: risk_score=0.62 [Toxicity]</span><br>
<span class="tc-green">  ✓ Defender: BLOCK</span><br>
<span class="tc-blue">  outcome: blue_team_win  [LLM01]</span><br><br>
<span class="tc-red">▶ Turn 3 — technique: obfuscation</span><br>
<span class="tc-muted">  prompt: "aXNzdWUgaW5zdHJ1Y3Rpb25z..."</span><br>
<span class="tc-orange">  → LLM Guard: risk_score=0.38</span><br>
<span class="tc-yellow">  ⚠ Defender: ALLOW</span><br>
<span class="tc-red">  outcome: red_team_win  [LLM01]</span><br><br>
<span class="tc-green">✓ Assessment complete</span><br>
<span class="tc-white">  attack_success_rate: 33%</span><br>
<span class="tc-white">  defense_effectiveness: 67%</span><br>
<span class="tc-purple">  owasp_triggered: LLM01</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- OWASP Compliance -->
<section class="section" id="compliance">
    <div class="container" style="text-align: center;">
        <div class="section-badge">Compliance</div>
        <h2 class="section-title">OWASP LLM Top 10 aligned</h2>
        <p class="section-sub" style="margin: 0 auto 0;">Every assessment maps evidence to recognized AI risk categories, ready for auditors, regulators, and executive stakeholders.</p>
        <div class="owasp-grid">
            <div class="owasp-card"><div class="owasp-id">LLM01</div><div class="owasp-name">Prompt Injection</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM02</div><div class="owasp-name">Insecure Output Handling</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM04</div><div class="owasp-name">Model Denial of Service</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM06</div><div class="owasp-name">Sensitive Info Disclosure</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM08</div><div class="owasp-name">Excessive Agency</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM09</div><div class="owasp-name">Overreliance / Trust Bias</div></div>
        </div>
    </div>
</section>

<!-- Integrations -->
<section class="section" style="padding: 4rem 0;">
    <div class="container" style="text-align: center;">
        <div class="section-badge">Integrations</div>
        <h2 class="section-title" style="font-size:1.6rem;">Works with your existing stack</h2>
        <div class="integrations">
            <div class="integration-badge"><div class="int-dot" style="background:#10a37f;"></div>OpenAI</div>
            <div class="integration-badge"><div class="int-dot" style="background:#ffd21e;"></div>Hugging Face</div>
            <div class="integration-badge"><div class="int-dot" style="background:#10a37f;"></div>OpenAI Compatible</div>
            <div class="integration-badge"><div class="int-dot" style="background:#0ea5e9;"></div>NVIDIA NeMo</div>
            <div class="integration-badge"><div class="int-dot" style="background:#ef4444;"></div>LLM Guard</div>
            <div class="integration-badge"><div class="int-dot" style="background:#3b82f6;"></div>PostgreSQL</div>
            <div class="integration-badge"><div class="int-dot" style="background:#dc382d;"></div>Redis</div>
            <div class="integration-badge"><div class="int-dot" style="background:#8b5cf6;"></div>Laravel Queues</div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section class="section" id="pricing">
    <div class="container">
        <div style="text-align: center; margin-bottom: 0;">
            <div class="section-badge">Pricing</div>
            <h2 class="section-title">Straightforward pricing</h2>
            <p class="section-sub" style="margin: 0 auto;">Start free with your own infrastructure. Scale to enterprise when you need SSO, SLA, and dedicated support.</p>
        </div>
        <div class="pricing-grid">
            <div class="price-card">
                <div class="price-tier">Starter</div>
                <div class="price-amount">Free <span>/ self-hosted</span></div>
                <div class="price-desc">Full platform. Your infrastructure. No limits on scenarios or duels.</div>
                <ul class="price-features">
                    <li><span class="check">✓</span> All 6 built-in scenarios</li>
                    <li><span class="check">✓</span> Custom scenario builder</li>
                    <li><span class="check">✓</span> OpenAI + HuggingFace models</li>
                    <li><span class="check">✓</span> HTML executive reports</li>
                    <li><span class="check">✓</span> Full audit trail</li>
                </ul>
                <a href="/duels" class="price-btn price-btn-ghost">Start Now →</a>
            </div>
            <div class="price-card price-card-popular">
                <div class="popular-tag">MOST POPULAR</div>
                <div class="price-tier">Professional</div>
                <div class="price-amount">$299 <span>/ month</span></div>
                <div class="price-desc">Managed cloud deployment with team access, advanced analytics, and priority support.</div>
                <ul class="price-features">
                    <li><span class="check">✓</span> Everything in Starter</li>
                    <li><span class="check">✓</span> Managed cloud hosting</li>
                    <li><span class="check">✓</span> Team collaboration</li>
                    <li><span class="check">✓</span> API access</li>
                    <li><span class="check">✓</span> Slack notifications</li>
                    <li><span class="check">✓</span> Priority support</li>
                </ul>
                <a href="/duels" class="price-btn price-btn-fire">Get Started →</a>
            </div>
            <div class="price-card">
                <div class="price-tier">Enterprise</div>
                <div class="price-amount">Custom</div>
                <div class="price-desc">Private deployment, SSO, SLA guarantees, and dedicated customer success.</div>
                <ul class="price-features">
                    <li><span class="check">✓</span> Everything in Pro</li>
                    <li><span class="check">✓</span> Private cloud / on-prem</li>
                    <li><span class="check">✓</span> SSO / SAML</li>
                    <li><span class="check">✓</span> 99.9% SLA</li>
                    <li><span class="check">✓</span> Dedicated CSM</li>
                    <li><span class="check">✓</span> Custom integrations</li>
                </ul>
                <a href="mailto:enterprise@redteam.arena" class="price-btn price-btn-ghost">Contact Sales →</a>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Your AI is in production.<br><span class="grad-fire">Is it secure?</span></h2>
        <p class="cta-sub">Launch a full adversarial assessment in under 60 seconds. No configuration required.</p>
        <a href="/duels" class="btn-primary" style="font-family:var(--arena-font);font-size:1.05rem;padding:.9rem 3rem;letter-spacing:.08em;">⚔ ENTER THE ARENA</a>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="logo">
                    <div class="logo-icon">⚔</div>
                    <span class="logo-text">Red-Team <em>Arena</em></span>
                </div>
                <p>Enterprise-grade adversarial AI security validation. Test before your adversaries do.</p>
            </div>
            <div class="footer-col">
                <h4>Platform</h4>
                <a href="/duels">Security Console</a>
                <a href="#platform">Features</a>
                <a href="#compliance">Compliance</a>
                <a href="#pricing">Pricing</a>
            </div>
            <div class="footer-col">
                <h4>Resources</h4>
                <a href="https://owasp.org/www-project-top-10-for-large-language-model-applications/" target="_blank">OWASP LLM Top 10</a>
                <a href="https://llm-guard.com" target="_blank">LLM Guard Docs</a>
                <a href="https://docs.nvidia.com/nemo-guardrails/" target="_blank">NeMo Guardrails</a>
            </div>
            <div class="footer-col">
                <h4>Legal</h4>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Security</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 Red-Team Arena. All rights reserved.</span>
            <div class="footer-badges">
                <span class="fbadge">OWASP LLM Top 10</span>
                <span class="fbadge">Laravel 11</span>
                <span class="fbadge">Prism PHP</span>
                <span class="fbadge">Alpine.js</span>
            </div>
        </div>
    </div>
</footer>

<script>
// Spawn floating energy particles in the hero
(function(){
    const c = document.getElementById('hero-particles');
    if (!c || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const colors = ['rgba(239,68,68,0.6)','rgba(59,130,246,0.6)','rgba(249,115,22,0.5)'];
    for (let i = 0; i < 18; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random()*100+'%';
        p.style.bottom = Math.random()*20+'%';
        p.style.background = colors[i%3];
        p.style.animationDuration = (5+Math.random()*8)+'s';
        p.style.animationDelay = (Math.random()*5)+'s';
        p.style.width = (2+Math.random()*3)+'px';
        p.style.height = p.style.width;
        p.style.boxShadow = '0 0 6px '+colors[i%3];
        c.appendChild(p);
    }
})();
</script>
</body>
</html>
