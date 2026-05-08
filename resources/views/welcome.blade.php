<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red-Team Arena — AI Safety Adversarial Simulation</title>
    <meta name="description" content="Autonomous multi-agent adversarial simulation platform for LLM safety testing. Red Team vs Blue Team in real-time AI duels.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #ef4444; --red-glow: rgba(239,68,68,0.4);
            --orange: #f97316; --orange-glow: rgba(249,115,22,0.3);
            --blue: #3b82f6; --blue-glow: rgba(59,130,246,0.3);
            --green: #22c55e; --purple: #a855f7;
            --bg: #050810; --panel: #0c1120;
            --border: rgba(255,255,255,0.07);
            --text: #e2e8f0; --muted: #64748b;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; scroll-behavior: smooth; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; overflow-x: hidden; }

        /* ── Ambient background ───────────────────────────────────── */
        .ambient {
            position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden;
        }
        .ambient .orb {
            position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.15;
            animation: float 20s ease-in-out infinite alternate;
        }
        .ambient .orb-red { width: 600px; height: 600px; background: var(--red); top: -10%; left: -5%; }
        .ambient .orb-blue { width: 500px; height: 500px; background: var(--blue); bottom: 10%; right: -5%; animation-delay: -7s; }
        .ambient .orb-orange { width: 400px; height: 400px; background: var(--orange); top: 50%; left: 40%; animation-delay: -14s; }
        @keyframes float {
            0% { transform: translate(0,0) scale(1); }
            50% { transform: translate(30px,-40px) scale(1.1); }
            100% { transform: translate(-20px,30px) scale(0.95); }
        }
        .grid-overlay {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ── Container ────────────────────────────────────────────── */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; position: relative; z-index: 1; }

        /* ── Navigation ───────────────────────────────────────────── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: rgba(5,8,16,0.8); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border); padding: 1rem 2rem;
        }
        .nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { display: flex; align-items: center; gap: .6rem; text-decoration: none; }
        .nav-icon {
            width: 2.2rem; height: 2.2rem; border-radius: .5rem;
            background: linear-gradient(135deg, var(--red), var(--orange));
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
        }
        .nav-logo span { font-size: 1.1rem; font-weight: 800; color: var(--text); }
        .nav-logo span em { font-style: normal; color: var(--orange); }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: .85rem; font-weight: 500; transition: color .2s; }
        .nav-links a:hover { color: var(--text); }
        .btn-enter {
            background: linear-gradient(135deg, var(--red), var(--orange));
            color: #fff; border: none; padding: .55rem 1.5rem; border-radius: .5rem;
            font-weight: 700; font-size: .85rem; cursor: pointer; text-decoration: none;
            transition: opacity .15s, transform .1s; letter-spacing: .02em;
        }
        .btn-enter:hover { opacity: .9; transform: translateY(-1px); }

        /* ── Hero ─────────────────────────────────────────────────── */
        .hero {
            min-height: 100vh; display: flex; flex-direction: column;
            align-items: center; justify-content: center; text-align: center;
            padding: 8rem 2rem 4rem;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: .5rem;
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);
            padding: .35rem 1rem; border-radius: 999px;
            font-size: .75rem; font-weight: 600; color: #f87171; margin-bottom: 2rem;
            animation: fadeInUp .8s ease;
        }
        .hero-badge .pulse-dot {
            width: 6px; height: 6px; border-radius: 50%; background: var(--red);
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }
        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 900;
            line-height: 1.1; letter-spacing: -.03em; margin-bottom: 1.5rem;
            animation: fadeInUp .8s ease .1s both;
        }
        .hero h1 .gradient { background: linear-gradient(135deg, var(--red), var(--orange), #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero h1 .blue-grad { background: linear-gradient(135deg, var(--blue), #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-sub {
            font-size: 1.15rem; color: var(--muted); max-width: 600px; line-height: 1.7;
            margin-bottom: 2.5rem; animation: fadeInUp .8s ease .2s both;
        }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; animation: fadeInUp .8s ease .3s both; }
        .btn-hero-primary {
            background: linear-gradient(135deg, var(--red), var(--orange));
            color: #fff; border: none; padding: .8rem 2.5rem; border-radius: .6rem;
            font-weight: 700; font-size: 1rem; cursor: pointer; text-decoration: none;
            transition: all .2s; letter-spacing: .02em;
            box-shadow: 0 0 30px var(--red-glow);
        }
        .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 0 50px var(--red-glow); }
        .btn-hero-secondary {
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            color: var(--text); padding: .8rem 2.5rem; border-radius: .6rem;
            font-weight: 600; font-size: 1rem; cursor: pointer; text-decoration: none;
            transition: all .2s;
        }
        .btn-hero-secondary:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* ── Versus Animation ─────────────────────────────────────── */
        .versus { display: flex; align-items: center; justify-content: center; gap: 2rem; margin: 3rem 0; animation: fadeInUp .8s ease .4s both; }
        .team-badge {
            display: flex; flex-direction: column; align-items: center; gap: .5rem;
            padding: 1.5rem 2.5rem; border-radius: 1rem;
            border: 1px solid var(--border); background: var(--panel);
            transition: all .3s;
        }
        .team-badge:hover { transform: translateY(-4px); }
        .team-red { border-color: rgba(239,68,68,0.3); box-shadow: 0 0 40px rgba(239,68,68,0.1); }
        .team-blue { border-color: rgba(59,130,246,0.3); box-shadow: 0 0 40px rgba(59,130,246,0.1); }
        .team-icon { font-size: 2rem; }
        .team-name { font-weight: 800; font-size: 1rem; letter-spacing: .04em; }
        .team-red .team-name { color: var(--red); }
        .team-blue .team-name { color: var(--blue); }
        .team-role { font-size: .72rem; color: var(--muted); }
        .vs-text { font-size: 1.5rem; font-weight: 900; color: var(--orange); text-shadow: 0 0 20px var(--orange-glow); }

        /* ── Features ─────────────────────────────────────────────── */
        .section { padding: 6rem 0; }
        .section-title {
            text-align: center; font-size: 2rem; font-weight: 800;
            margin-bottom: .75rem; letter-spacing: -.02em;
        }
        .section-sub { text-align: center; color: var(--muted); font-size: 1rem; margin-bottom: 3rem; max-width: 600px; margin-left: auto; margin-right: auto; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        @media(max-width:768px) { .features-grid { grid-template-columns: 1fr; } }
        .feature-card {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: .75rem; padding: 1.5rem; transition: all .3s;
            position: relative; overflow: hidden;
        }
        .feature-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--red), var(--orange), transparent);
            opacity: 0; transition: opacity .3s;
        }
        .feature-card:hover { transform: translateY(-4px); border-color: rgba(255,255,255,0.12); }
        .feature-card:hover::before { opacity: 1; }
        .feature-icon { font-size: 1.8rem; margin-bottom: .75rem; }
        .feature-title { font-weight: 700; font-size: 1rem; margin-bottom: .4rem; }
        .feature-desc { font-size: .82rem; color: var(--muted); line-height: 1.6; }

        /* ── How It Works ─────────────────────────────────────────── */
        .steps { display: flex; flex-direction: column; gap: 0; max-width: 700px; margin: 0 auto; }
        .step {
            display: flex; gap: 1.5rem; padding: 1.5rem 0; position: relative;
        }
        .step-num {
            width: 3rem; height: 3rem; flex-shrink: 0; border-radius: 50%;
            background: linear-gradient(135deg, var(--red), var(--orange));
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 1rem; color: #fff; position: relative; z-index: 1;
        }
        .step::after {
            content: ''; position: absolute; left: calc(1rem + 1.5px); top: 4.5rem; bottom: 0;
            width: 1px; background: rgba(255,255,255,0.08);
        }
        .step:last-child::after { display: none; }
        .step-content { flex: 1; padding-top: .4rem; }
        .step-title { font-weight: 700; font-size: 1rem; margin-bottom: .3rem; }
        .step-desc { font-size: .85rem; color: var(--muted); line-height: 1.6; }

        /* ── OWASP Section ────────────────────────────────────────── */
        .owasp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        @media(max-width:768px) { .owasp-grid { grid-template-columns: repeat(2, 1fr); } }
        .owasp-card {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: .6rem; padding: 1rem 1.2rem; text-align: center;
            transition: all .3s;
        }
        .owasp-card:hover { border-color: rgba(239,68,68,0.3); transform: translateY(-2px); }
        .owasp-id { font-family: 'JetBrains Mono', monospace; font-weight: 700; color: var(--red); font-size: .9rem; }
        .owasp-name { font-size: .75rem; color: var(--muted); margin-top: .25rem; }

        /* ── CTA ──────────────────────────────────────────────────── */
        .cta-section {
            text-align: center; padding: 5rem 2rem;
            background: linear-gradient(180deg, transparent, rgba(239,68,68,0.05), transparent);
            border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
        }
        .cta-title { font-size: 2.2rem; font-weight: 900; margin-bottom: 1rem; }
        .cta-sub { color: var(--muted); margin-bottom: 2rem; font-size: 1rem; }

        /* ── Footer ───────────────────────────────────────────────── */
        footer {
            text-align: center; padding: 2rem; color: var(--muted);
            font-size: .75rem; border-top: 1px solid var(--border);
        }
        footer a { color: var(--orange); text-decoration: none; }
    </style>
</head>
<body>

<!-- Ambient Background -->
<div class="ambient">
    <div class="orb orb-red"></div>
    <div class="orb orb-blue"></div>
    <div class="orb orb-orange"></div>
</div>
<div class="grid-overlay"></div>

<!-- Navigation -->
<nav>
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <div class="nav-icon">⚔</div>
            <span>Red-Team <em>Arena</em></span>
        </a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#owasp">OWASP</a>
            <a href="/duels" class="btn-enter">Enter Arena ⚡</a>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-badge">
        <span class="pulse-dot"></span>
        Autonomous Multi-Agent AI Safety Platform
    </div>
    <h1>
        <span class="gradient">Red Team</span> vs <span class="blue-grad">Blue Team</span><br>
        LLM Safety Arena
    </h1>
    <p class="hero-sub">
        Pit adversarial AI agents against defensive guardrails in autonomous duels.
        Discover vulnerabilities, test defenses, and harden your models — all mapped to the OWASP LLM Top 10.
    </p>
    <div class="hero-actions">
        <a href="/duels" class="btn-hero-primary">⚡ Start a Duel</a>
        <a href="#how-it-works" class="btn-hero-secondary">How It Works →</a>
    </div>

    <div class="versus">
        <div class="team-badge team-red">
            <div class="team-icon">🗡️</div>
            <div class="team-name">ATTACKER</div>
            <div class="team-role">10 Techniques · Adaptive AI</div>
        </div>
        <div class="vs-text">VS</div>
        <div class="team-badge team-blue">
            <div class="team-icon">🛡️</div>
            <div class="team-name">DEFENDER</div>
            <div class="team-role">3 Policy Profiles · Guardrails</div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section" id="features">
    <div class="container">
        <h2 class="section-title">Platform Capabilities</h2>
        <p class="section-sub">A comprehensive toolkit for adversarial testing of large language models.</p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <div class="feature-title">3 Autonomous Agents</div>
                <div class="feature-desc">Attacker, Defender, and Policy Judge agents powered by Groq LLMs operate independently with distinct strategies.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🧠</div>
                <div class="feature-title">Adaptive Attacks</div>
                <div class="feature-desc">The Attacker agent tracks technique effectiveness, rotates blocked families, and escalates sophistication dynamically.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <div class="feature-title">Multi-Layer Defense</div>
                <div class="feature-desc">NeMo Guardrails + LLM Guard + AI Defender with strict/moderate/permissive policy enforcement.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <div class="feature-title">OWASP LLM Top 10</div>
                <div class="feature-desc">Every turn is scored against OWASP categories: LLM01 injection, LLM02 output handling, LLM06 PII, and more.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">Real-Time Duels</div>
                <div class="feature-desc">Watch attacks and defenses unfold turn-by-turn in a live arena with instant verdicts and scoring.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <div class="feature-title">Analytics Engine</div>
                <div class="feature-desc">Cross-scenario metrics, technique breakdowns, model comparisons, and policy effectiveness analysis.</div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section" id="how-it-works" style="background: linear-gradient(180deg, transparent, rgba(59,130,246,0.03), transparent);">
    <div class="container">
        <h2 class="section-title">How a Duel Works</h2>
        <p class="section-sub">Each turn follows an 8-step pipeline — from adversarial generation to final judgment.</p>
        <div class="steps">
            <div class="step"><div class="step-num">1</div><div class="step-content"><div class="step-title">Attacker Generates Prompt</div><div class="step-desc">The Red Team agent crafts an adversarial prompt using techniques like jailbreaking, role-play framing, or token smuggling.</div></div></div>
            <div class="step"><div class="step-num">2</div><div class="step-content"><div class="step-title">Input Guardrail Scan</div><div class="step-desc">NeMo Guardrails and LLM Guard scan the adversarial prompt for injection patterns, PII extraction, and harmful intent.</div></div></div>
            <div class="step"><div class="step-num">3</div><div class="step-content"><div class="step-title">Target Model Response</div><div class="step-desc">The prompt is sent to the target LLM with its safety system prompt. The model's response is captured with latency and token metrics.</div></div></div>
            <div class="step"><div class="step-num">4</div><div class="step-content"><div class="step-title">Output Guardrail Scan</div><div class="step-desc">The model's response is scanned for toxicity, PII leakage, system prompt disclosure, and self-harm content.</div></div></div>
            <div class="step"><div class="step-num">5</div><div class="step-content"><div class="step-title">Defender Verdict</div><div class="step-desc">The Blue Team Defender evaluates the response against the policy profile and issues ALLOW, BLOCK, or MODIFY.</div></div></div>
            <div class="step"><div class="step-num">6</div><div class="step-content"><div class="step-title">Policy Judge Scores</div><div class="step-desc">The referee agent scores the turn as red_team_win, blue_team_win, draw, or false_positive with OWASP mapping.</div></div></div>
        </div>
    </div>
</section>

<!-- OWASP Categories -->
<section class="section" id="owasp">
    <div class="container">
        <h2 class="section-title">OWASP LLM Top 10 Coverage</h2>
        <p class="section-sub">Every attack and defense is mapped to industry-standard vulnerability categories.</p>
        <div class="owasp-grid">
            <div class="owasp-card"><div class="owasp-id">LLM01</div><div class="owasp-name">Prompt Injection</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM02</div><div class="owasp-name">Insecure Output</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM04</div><div class="owasp-name">Model DoS</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM06</div><div class="owasp-name">Sensitive Info Disclosure</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM08</div><div class="owasp-name">Excessive Agency</div></div>
            <div class="owasp-card"><div class="owasp-id">LLM09</div><div class="owasp-name">Overreliance</div></div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <h2 class="cta-title">Ready to Test Your Model's Defenses?</h2>
    <p class="cta-sub">Select a scenario, choose your policy, and let the agents battle it out.</p>
    <a href="/duels" class="btn-hero-primary" style="font-size:1.1rem;padding:.9rem 3rem;">⚔ Enter the Arena</a>
</section>

<!-- Footer -->
<footer>
    <p>Red-Team Arena — Built with Laravel, Prism PHP, and Groq AI &nbsp;·&nbsp; <a href="https://owasp.org/www-project-top-10-for-large-language-model-applications/" target="_blank">OWASP LLM Top 10</a></p>
</footer>

</body>
</html>
