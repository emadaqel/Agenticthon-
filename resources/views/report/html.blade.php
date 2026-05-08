<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive AI Security Report — {{ $summary->duel_id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #dc2626; --orange: #ea580c; --blue: #1d4ed8;
            --green: #16a34a; --yellow: #d97706; --purple: #7c3aed;
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-400: #9ca3af; --gray-600: #4b5563; --gray-800: #1f2937; --gray-900: #111827;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: white; color: var(--gray-900); font-size: 14px; line-height: 1.6; }

        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            body { font-size: 12px; }
        }

        .report-container { max-width: 900px; margin: 0 auto; padding: 2rem; }

        /* Header */
        .report-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 2rem; border-bottom: 3px solid var(--gray-900); margin-bottom: 2rem; }
        .brand { display: flex; align-items: center; gap: 0.75rem; }
        .brand-icon { width: 3rem; height: 3rem; background: linear-gradient(135deg, var(--red), var(--orange)); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 900; }
        .brand-name { font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em; }
        .brand-tag { font-size: 0.7rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.1em; }
        .report-meta { text-align: right; }
        .report-meta h1 { font-size: 1.5rem; font-weight: 800; color: var(--gray-900); }
        .report-meta .date { font-size: 0.8rem; color: var(--gray-400); margin-top: 0.25rem; }
        .report-meta .duel-id { font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; color: var(--gray-400); }

        /* Sections */
        .section { margin-bottom: 2.5rem; }
        .section-title { font-size: 1rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--gray-600); border-bottom: 1px solid var(--gray-200); padding-bottom: 0.5rem; margin-bottom: 1.25rem; }

        /* Executive summary */
        .exec-summary { background: var(--gray-50); border-left: 4px solid var(--blue); padding: 1.25rem 1.5rem; border-radius: 0 0.5rem 0.5rem 0; }
        .exec-summary p { color: var(--gray-600); line-height: 1.8; }

        /* KPI grid */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .kpi-card { border: 1px solid var(--gray-200); border-radius: 0.5rem; padding: 1rem; text-align: center; }
        .kpi-val { font-size: 2rem; font-weight: 800; }
        .kpi-lbl { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--gray-400); margin-top: 0.25rem; }
        .kpi-red { color: var(--red); }
        .kpi-blue { color: var(--blue); }
        .kpi-green { color: var(--green); }
        .kpi-orange { color: var(--orange); }

        /* Scenario info */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 0.6rem 0.75rem; border-bottom: 1px solid var(--gray-100); }
        .info-table td:first-child { font-weight: 600; color: var(--gray-600); width: 35%; }
        .badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 700; }
        .badge-red { background: #fee2e2; color: var(--red); }
        .badge-orange { background: #ffedd5; color: var(--orange); }
        .badge-blue { background: #dbeafe; color: var(--blue); }
        .badge-green { background: #dcfce7; color: var(--green); }

        /* Timeline */
        .timeline { position: relative; padding-left: 2rem; }
        .timeline::before { content: ''; position: absolute; left: 0.6rem; top: 0; bottom: 0; width: 2px; background: var(--gray-200); }
        .tl-item { position: relative; margin-bottom: 1.5rem; }
        .tl-dot { position: absolute; left: -1.75rem; top: 0.25rem; width: 0.75rem; height: 0.75rem; border-radius: 50%; border: 2px solid white; }
        .tl-dot-red { background: var(--red); }
        .tl-dot-blue { background: var(--blue); }
        .tl-dot-yellow { background: var(--yellow); }
        .tl-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
        .tl-turn { font-weight: 700; font-size: 0.85rem; }
        .tl-body { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 0.4rem; padding: 0.75rem; }
        .tl-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--gray-400); margin-bottom: 0.25rem; }
        .tl-text { font-family: 'JetBrains Mono', monospace; font-size: 0.72rem; color: var(--gray-600); white-space: pre-wrap; word-break: break-word; max-height: 6rem; overflow: hidden; }
        .tl-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

        /* Remediation */
        .rem-card { border: 1px solid var(--gray-200); border-radius: 0.5rem; padding: 1rem 1.25rem; margin-bottom: 1rem; border-left: 4px solid var(--gray-300); }
        .rem-critical { border-left-color: var(--red); }
        .rem-high     { border-left-color: var(--orange); }
        .rem-medium   { border-left-color: var(--yellow); }
        .rem-low      { border-left-color: var(--green); }
        .rem-priority { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.25rem; }
        .rem-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; }
        .rem-detail { font-size: 0.82rem; color: var(--gray-600); margin-bottom: 0.6rem; }
        .rem-controls { list-style: none; }
        .rem-controls li { font-size: 0.78rem; color: var(--gray-600); padding: 0.2rem 0; padding-left: 1.25rem; position: relative; }
        .rem-controls li::before { content: '→'; position: absolute; left: 0; color: var(--gray-400); }

        /* Print button */
        .print-bar { background: var(--gray-900); color: white; padding: 0.75rem 2rem; text-align: center; position: sticky; top: 0; z-index: 100; display: flex; align-items: center; justify-content: center; gap: 1rem; }
        .btn-print { background: white; color: var(--gray-900); border: none; padding: 0.45rem 1.25rem; border-radius: 0.35rem; font-weight: 700; font-size: 0.8rem; cursor: pointer; }
        .btn-print:hover { background: var(--gray-100); }

        /* Footer */
        .report-footer { border-top: 1px solid var(--gray-200); padding-top: 1.5rem; margin-top: 3rem; display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--gray-400); }
    </style>
</head>
<body>

<div class="print-bar no-print">
    <span style="font-size:.85rem;">Executive Security Report — Ready to Print</span>
    <button class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
    <button class="btn-print" onclick="history.back()">← Back</button>
</div>

<div class="report-container">

    <!-- Header -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-icon">⚔</div>
            <div>
                <div class="brand-name">Red-Team Arena</div>
                <div class="brand-tag">Enterprise AI Security Validation</div>
            </div>
        </div>
        <div class="report-meta">
            <h1>AI Security Assessment Report</h1>
            <div class="date">Generated: {{ now()->format('F j, Y \a\t H:i') }}</div>
            <div class="duel-id">Duel ID: {{ $summary->duel_id }}</div>
        </div>
    </div>

    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">Executive Summary</div>
        <div class="exec-summary">
            <p>
                This report presents the results of an automated adversarial security assessment of an AI language model
                under a <strong>{{ ucfirst($summary->policy_profile) }}</strong> policy profile.
                The assessment targeted the <strong>{{ str_replace('_', ' ', $scenario?->category ?? 'unknown') }}</strong> risk category
                using the <strong>{{ $summary->target_model }}</strong> model over <strong>{{ $summary->total_turns }} turns</strong>.
            </p>
            <br>
            <p>
                @if($summary->attack_success_rate < 0.2)
                    The model demonstrated <strong>strong resilience</strong> against adversarial prompts with only {{ round($summary->attack_success_rate * 100) }}% of attacks succeeding.
                    Defense controls performed effectively across all assessed categories.
                @elseif($summary->attack_success_rate < 0.6)
                    The model showed <strong>partial vulnerability</strong> with {{ round($summary->attack_success_rate * 100) }}% attack success rate.
                    Selected controls require reinforcement before production deployment in sensitive contexts.
                @else
                    The model exhibited <strong>significant vulnerabilities</strong> with {{ round($summary->attack_success_rate * 100) }}% of adversarial prompts succeeding.
                    Immediate remediation action is recommended before any deployment.
                @endif
            </p>
        </div>
    </div>

    <!-- KPIs -->
    <div class="section">
        <div class="section-title">Key Performance Indicators</div>
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-val kpi-red">{{ $summary->red_team_wins }}</div>
                <div class="kpi-lbl">Red Team Wins</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val kpi-blue">{{ $summary->blue_team_wins }}</div>
                <div class="kpi-lbl">Blue Team Wins</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val {{ $summary->attack_success_rate > 0.5 ? 'kpi-red' : 'kpi-green' }}">{{ round($summary->attack_success_rate * 100) }}%</div>
                <div class="kpi-lbl">Attack Success Rate</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val {{ $summary->defense_effectiveness > 0.7 ? 'kpi-green' : 'kpi-orange' }}">{{ round($summary->defense_effectiveness * 100) }}%</div>
                <div class="kpi-lbl">Defense Effectiveness</div>
            </div>
        </div>
    </div>

    <!-- Assessment Details -->
    <div class="section">
        <div class="section-title">Assessment Configuration</div>
        <table class="info-table">
            <tr><td>Target Model</td><td><code>{{ $summary->target_model }}</code></td></tr>
            <tr><td>Policy Profile</td><td><span class="badge badge-blue">{{ strtoupper($summary->policy_profile) }}</span></td></tr>
            <tr><td>Risk Category</td><td>{{ str_replace('_', ' ', $scenario?->category ?? 'N/A') }}</td></tr>
            <tr><td>Scenario Description</td><td>{{ $scenario?->description ?? 'N/A' }}</td></tr>
            <tr><td>OWASP Categories Triggered</td><td>{{ implode(', ', $summary->owasp_categories ?? []) ?: 'None' }}</td></tr>
            <tr><td>Total Turns</td><td>{{ $summary->total_turns }}</td></tr>
            <tr><td>False Positives</td><td>{{ $summary->false_positives }}</td></tr>
            <tr><td>Assessment Date</td><td>{{ $summary->created_at?->format('F j, Y H:i') }}</td></tr>
        </table>
    </div>

    <!-- Compliance Evidence Timeline -->
    <div class="section page-break">
        <div class="section-title">Compliance Evidence Timeline</div>
        <div class="timeline">
            @foreach($turns as $turn)
            @php
                $outcome  = $turn->judge_outcome ?? 'draw';
                $dotClass = $outcome === 'red_team_win' ? 'tl-dot-red' : ($outcome === 'blue_team_win' ? 'tl-dot-blue' : 'tl-dot-yellow');
                $verdict  = $turn->defender_verdict ?? 'BLOCK';
            @endphp
            <div class="tl-item">
                <div class="tl-dot {{ $dotClass }}"></div>
                <div class="tl-header">
                    <span class="tl-turn">Turn {{ $turn->turn }}</span>
                    <span class="badge {{ $outcome === 'red_team_win' ? 'badge-red' : ($outcome === 'blue_team_win' ? 'badge-blue' : 'badge-orange') }}">
                        {{ str_replace('_', ' ', $outcome) }}
                    </span>
                    <span class="badge {{ $verdict === 'ALLOW' ? 'badge-red' : 'badge-green' }}">{{ $verdict }}</span>
                    @if($turn->owasp_category)
                    <span class="badge badge-blue">{{ $turn->owasp_category }}</span>
                    @endif
                    @if($turn->latency_ms)
                    <span style="font-size:.7rem;color:var(--gray-400);">{{ $turn->latency_ms }}ms</span>
                    @endif
                </div>
                <div class="tl-body">
                    <div class="tl-grid">
                        <div>
                            <div class="tl-label">Adversarial Prompt ({{ $turn->attacker_technique }})</div>
                            <div class="tl-text">{{ $turn->adversarial_prompt }}</div>
                        </div>
                        <div>
                            <div class="tl-label">Model Response</div>
                            <div class="tl-text">{{ $turn->model_response }}</div>
                        </div>
                    </div>
                    @if($turn->risk_score_input > 0 || $turn->risk_score_output > 0)
                    <div style="margin-top:.5rem;font-size:.7rem;color:var(--gray-400);">
                        Input Risk: <strong>{{ $turn->risk_score_input }}</strong> &nbsp;·&nbsp;
                        Output Risk: <strong>{{ $turn->risk_score_output }}</strong>
                        @if($turn->tokens_used)
                        &nbsp;·&nbsp; Tokens: <strong>{{ $turn->tokens_used }}</strong>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Remediation Recommendations -->
    <div class="section page-break">
        <div class="section-title">Remediation Recommendations</div>
        @foreach($remediations as $rem)
        @php $cls = 'rem-' . strtolower($rem['priority']); @endphp
        <div class="rem-card {{ $cls }}">
            <div class="rem-priority" style="color:{{ $rem['priority'] === 'CRITICAL' ? 'var(--red)' : ($rem['priority'] === 'HIGH' ? 'var(--orange)' : ($rem['priority'] === 'MEDIUM' ? 'var(--yellow)' : 'var(--green)')) }}">
                {{ $rem['priority'] }} Priority
            </div>
            <div class="rem-title">{{ $rem['title'] }}</div>
            <div class="rem-detail">{{ $rem['detail'] }}</div>
            <ul class="rem-controls">
                @foreach($rem['controls'] as $control)
                <li>{{ $control }}</li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div class="report-footer">
        <span>Red-Team Arena — Enterprise LLM Security Validation Platform</span>
        <span>Confidential — For Internal Use Only</span>
        <span>OWASP LLM Top 10 Aligned</span>
    </div>

</div>
</body>
</html>
