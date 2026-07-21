<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Security Workspace · Red-Team Arena</title>
    <style>
        :root { color-scheme: dark; --bg:#070a0f; --panel:#10151d; --line:#25303d; --ink:#f3f5f7; --muted:#94a3b8; --green:#34d399; --red:#fb7185; --amber:#fbbf24; --blue:#60a5fa; }
        * { box-sizing:border-box; } body { margin:0; background:radial-gradient(circle at 80% 0,#12263d 0,transparent 30%),var(--bg); color:var(--ink); font:15px/1.55 Inter,ui-sans-serif,system-ui,sans-serif; }
        a { color:inherit; } button,input,textarea { font:inherit; } .shell { max-width:1240px; margin:auto; padding:28px 22px 64px; }
        header { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:34px; } .brand { font-weight:800; letter-spacing:.08em; text-transform:uppercase; } .brand span { color:var(--blue); }
        nav { display:flex; gap:18px; color:var(--muted); font-size:13px; } nav a { text-decoration:none; } nav a:hover { color:var(--ink); }
        .hero { display:grid; grid-template-columns:1.6fr 1fr; gap:24px; align-items:end; margin-bottom:24px; } h1 { font-size:clamp(35px,6vw,72px); line-height:.98; letter-spacing:-.055em; margin:0; max-width:850px; } .lead { color:var(--muted); max-width:520px; margin:18px 0 0; }
        .eyebrow,.mono { font:12px/1.4 ui-monospace,SFMono-Regular,Consolas,monospace; letter-spacing:.08em; text-transform:uppercase; color:var(--blue); } .status { border:1px solid var(--line); background:rgba(16,21,29,.8); border-radius:16px; padding:20px; }
        .dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--green); margin-right:7px; box-shadow:0 0 14px var(--green); }
        .grid { display:grid; grid-template-columns:repeat(12,1fr); gap:16px; } .card { grid-column:span 4; border:1px solid var(--line); background:rgba(16,21,29,.92); border-radius:16px; padding:20px; min-width:0; } .wide { grid-column:span 8; } .full { grid-column:1/-1; }
        .card h2 { font-size:16px; margin:0 0 6px; } .sub { color:var(--muted); font-size:13px; margin:0 0 18px; } .metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; } .metric { border:1px solid var(--line); border-radius:12px; padding:14px; } .metric strong { display:block; font-size:28px; line-height:1; margin-top:7px; } .good { color:var(--green); } .bad { color:var(--red); } .warn { color:var(--amber); }
        table { width:100%; border-collapse:collapse; font-size:13px; } th,td { padding:11px 8px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; } th { color:var(--muted); font-weight:500; } .pill { display:inline-flex; border:1px solid currentColor; border-radius:999px; padding:2px 8px; font-size:11px; text-transform:uppercase; letter-spacing:.05em; }
        .flow { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin:16px 0; } .node { padding:9px 12px; border:1px solid var(--line); background:#0b1017; border-radius:10px; font-size:12px; } .arrow { color:var(--muted); }
        form { display:grid; gap:10px; } input,textarea { width:100%; background:#090d13; color:var(--ink); border:1px solid var(--line); border-radius:10px; padding:11px 12px; outline:none; } input:focus,textarea:focus { border-color:var(--blue); } textarea { min-height:105px; resize:vertical; }
        button { border:0; border-radius:10px; padding:11px 15px; background:var(--blue); color:#07111f; font-weight:800; cursor:pointer; } button.secondary { background:#1b2634; color:var(--ink); } button:disabled { opacity:.55; cursor:wait; } .actions { display:flex; gap:9px; flex-wrap:wrap; }
        .log { margin-top:12px; padding:12px; border-radius:10px; background:#090d13; color:var(--muted); font:12px/1.55 ui-monospace,monospace; min-height:44px; white-space:pre-wrap; overflow-wrap:anywhere; } .empty { color:var(--muted); padding:18px 0; }
        @media(max-width:850px) { .hero { grid-template-columns:1fr; } .card,.wide { grid-column:1/-1; } .metrics { grid-template-columns:1fr; } header { align-items:flex-start; } nav { flex-wrap:wrap; justify-content:flex-end; } }
    </style>
</head>
<body>
<main class="shell">
    <header>
        <div class="brand">Red-Team <span>Arena</span></div>
        <nav><a href="/">Home</a><a href="/duels">Duels</a><a href="/scenarios">Scenarios</a><a href="/promptfoo">Promptfoo</a></nav>
    </header>

    <section class="hero">
        <div><div class="eyebrow">Security evidence, not security theater</div><h1>Trace attacks. Prove controls. Ship safely.</h1><p class="lead">A single workspace for reproducible prompt corpora, agent attack paths, structured findings, reviewed remediations, and release gates.</p></div>
        <div class="status"><div><span class="dot"></span>Deterministic demo online</div><p class="sub" style="margin:8px 0 0">Every result includes a corpus fingerprint and explicit control limitations.</p></div>
    </section>

    <section class="grid">
        <article class="card wide">
            <div class="eyebrow">Before / after proof</div><h2>Intentionally vulnerable support agent</h2><p class="sub">Same prompts, same benign case, one reviewed remediation.</p>
            <div class="metrics" id="demo-metrics"><div class="metric">Before failures<strong>—</strong></div><div class="metric">After failures<strong>—</strong></div><div class="metric">Benign preserved<strong>—</strong></div></div>
            <div id="demo-results" class="empty">Loading security proof…</div>
        </article>
        <article class="card">
            <div class="eyebrow">Release gate</div><h2>Evidence policy</h2><p class="sub">Builds fail on attack regressions, critical findings, or unavailable controls.</p>
            <div class="flow"><span class="node">Corpus</span><span class="arrow">→</span><span class="node">Run</span><span class="arrow">→</span><span class="node">Findings</span><span class="arrow">→</span><span class="node">Gate</span></div>
            <div class="log">max_failed_cases = 0
max_critical_findings = 0
unavailable_control = fail</div>
        </article>

        <article class="card">
            <div class="eyebrow">Versioned corpus</div><h2>Import from GitHub</h2><p class="sub">Supports Promptfoo YAML and JSON from github.com or raw.githubusercontent.com.</p>
            <form id="corpus-form"><input name="name" value="OWASP agent prompts" aria-label="Corpus name" required><input name="source_url" placeholder="https://raw.githubusercontent.com/…/prompts.yaml" aria-label="GitHub source URL" required><input name="source_ref" value="main" aria-label="Source ref"><button type="submit">Import and fingerprint</button></form>
            <div class="log" id="corpus-log">No import requested.</div>
        </article>
        <article class="card wide">
            <div class="eyebrow">Agentic attack path</div><h2>Trust-boundary analyzer</h2><p class="sub">The sample maps untrusted input through a model and broad-permission tool to sensitive data.</p>
            <div class="flow"><span class="node">User input</span><span class="arrow">→</span><span class="node">Agent model</span><span class="arrow">→</span><span class="node">Admin tool</span><span class="arrow">→</span><span class="node">Customer vault</span></div>
            <div class="actions"><button id="analyze">Analyze sample path</button><button class="secondary" id="mitigate">Analyze with controls</button></div>
            <div class="log" id="attack-log">Ready to analyze.</div>
        </article>

        <article class="card full">
            <div class="eyebrow">Control evidence</div><h2>Truthful report semantics</h2><p class="sub">Reports distinguish effective, bypassed, not triggered, unavailable, and not evaluated—so missing telemetry is never presented as protection.</p>
            <div class="metrics"><div class="metric"><span class="pill good">Effective</span><strong class="good">✓</strong></div><div class="metric"><span class="pill bad">Bypassed</span><strong class="bad">!</strong></div><div class="metric"><span class="pill warn">Unavailable</span><strong class="warn">∅</strong></div></div>
        </article>
    </section>
</main>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const request = async (url, options = {}) => {
    const response = await fetch(url, {headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,...options.headers}, ...options});
    const body = await response.json();
    if (!response.ok) throw new Error(body.message || JSON.stringify(body.errors || body));
    return body;
};
const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));

async function loadDemo() {
    try {
        const {demo} = await request('/api/security/demo');
        document.getElementById('demo-metrics').innerHTML = `<div class="metric">Before failures<strong class="bad">${demo.before.failed}</strong></div><div class="metric">After failures<strong class="good">${demo.after.failed}</strong></div><div class="metric">Benign preserved<strong class="good">${demo.improvement.benign_pass_rate_preserved ? 'YES' : 'NO'}</strong></div>`;
        document.getElementById('demo-results').innerHTML = `<table><thead><tr><th>Case</th><th>Before</th><th>After</th></tr></thead><tbody>${demo.before.results.map((before, index) => { const after=demo.after.results[index]; return `<tr><td>${escapeHtml(before.case_id)}</td><td><span class="pill ${before.passed?'good':'bad'}">${before.passed?'pass':'exploited'}</span><br>${escapeHtml(before.response)}</td><td><span class="pill ${after.passed?'good':'bad'}">${after.passed?'pass':'fail'}</span><br>${escapeHtml(after.response)}</td></tr>` }).join('')}</tbody></table><div class="log">corpus_sha256: ${demo.corpus_hash}</div>`;
    } catch (error) { document.getElementById('demo-results').textContent = error.message; }
}

document.getElementById('corpus-form').addEventListener('submit', async event => {
    event.preventDefault(); const button=event.currentTarget.querySelector('button'); const log=document.getElementById('corpus-log'); button.disabled=true; log.textContent='Fetching and validating source…';
    try { const data=Object.fromEntries(new FormData(event.currentTarget)); const {corpus}=await request('/api/corpora',{method:'POST',body:JSON.stringify(data)}); log.textContent=`Imported ${corpus.cases_count} cases\nsha256: ${corpus.content_hash}\nrevision: ${corpus.revision}`; }
    catch(error) { log.textContent=`Import failed: ${error.message}`; } finally { button.disabled=false; }
});

const graph = controlled => ({name:controlled?'Support agent · controlled':'Support agent · exposed',components:[{id:'input',name:'User input',type:'input',trust:'untrusted',sensitivity:'public'},{id:'agent',name:'Agent model',type:'model',trust:'trusted',sensitivity:'internal'},{id:'tool',name:'Admin tool',type:'tool',trust:'trusted',sensitivity:'internal',permissions:['customers.read','customers.write']},{id:'vault',name:'Customer vault',type:'data',trust:'trusted',sensitivity:'sensitive'}],connections:[{from:'input',to:'agent',sanitized:controlled},{from:'agent',to:'tool',approval_required:controlled},{from:'tool',to:'vault'}]});
async function analyze(controlled) { const log=document.getElementById('attack-log'); log.textContent='Tracing reachable sensitive components…'; try { const {assessment}=await request('/api/attack-surfaces/analyze',{method:'POST',body:JSON.stringify(graph(controlled))}); const path=assessment.attack_paths[0]; log.textContent=path ? `${path.severity} · risk ${path.risk_score}\n${path.labels.join(' → ')}\n${assessment.recommendations.map(item=>'• '+item.action).join('\n') || 'Controls break the highest-risk path.'}` : 'No sensitive path is reachable.'; } catch(error) { log.textContent=error.message; } }
document.getElementById('analyze').addEventListener('click',()=>analyze(false)); document.getElementById('mitigate').addEventListener('click',()=>analyze(true)); loadDemo();
</script>
</body>
</html>
