# ⚔ Red-Team Arena

> Autonomous Multi-Agent Adversarial Simulation Platform for LLM Safety Testing

Red-Team Arena pits a **Red Team Attacker** against a **Blue Team Defender** in real-time AI duels, with a **Policy Judge** referee scoring every turn against the **OWASP LLM Top 10**. All three agents reason using **OpenAI's `gpt-4o-mini`**, independent of whichever model is under test.

## Architecture

```
┌──────────────┐     ┌────────────────┐     ┌──────────────────┐
│  Attacker    │────▶│  NeMo + LLM    │────▶│   Target Model    │
│  Agent (Red) │     │  Guard (Input) │     │ (Groq / OpenAI /  │
│ gpt-4o-mini  │     │                │     │  Hugging Face)    │
└──────────────┘     └────────────────┘     └────────┬─────────┘
                                                      │
┌──────────────┐     ┌────────────────┐     ┌────────▼─────────┐
│ Policy Judge │◀────│   Defender     │◀────│  NeMo + LLM      │
│ gpt-4o-mini  │     │  Agent (Blue)  │     │  Guard (Output)  │
│  (Referee)   │     │  gpt-4o-mini   │     │                  │
└──────────────┘     └────────────────┘     └──────────────────┘
```

## How This Project Uses OpenAI Models

Red-Team Arena's entire adversarial loop is driven by OpenAI models — not just the model under test:

| Agent | Role | Model | Provider |
|-------|------|-------|----------|
| `AttackerAgent` | Crafts adversarial prompts, adapts technique on block | `gpt-4o-mini` | OpenAI ([app/AI/Agents/AttackerAgent.php](app/AI/Agents/AttackerAgent.php)) |
| `DefenderAgent` | Evaluates target output against policy profile | `gpt-4o-mini` | OpenAI ([app/AI/Agents/DefenderAgent.php](app/AI/Agents/DefenderAgent.php)) |
| `PolicyJudgeAgent` | Scores each turn and maps it to OWASP LLM Top 10 | `gpt-4o-mini` | OpenAI ([app/AI/Agents/PolicyJudgeAgent.php](app/AI/Agents/PolicyJudgeAgent.php)) |

These three agents are the "brains" of every duel: the attacker's reasoning, the defender's verdicts, and the judge's scoring are all OpenAI completions, orchestrated through [Prism PHP](https://prismphp.com) in [ModelGateway.php](app/Services/ModelGateway.php).

Separately, the **target model** — the system actually being red-teamed — is configurable per duel and can be OpenAI, Groq (OpenAI-compatible API), or Hugging Face-hosted models, so the same OpenAI-powered attacker/defender/judge pipeline can be pointed at any model under evaluation.

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | Laravel 11 (PHP 8.2+) |
| AI SDK | Prism PHP v0.100.1 |
| Agent Reasoning | OpenAI `gpt-4o-mini` (Attacker, Defender, Policy Judge) |
| Target Model Providers | OpenAI, Groq (llama-3.1/3.3), Hugging Face |
| Database | PostgreSQL |
| Cache/Queue | Redis |
| Guardrails | NeMo Guardrails + LLM Guard |
| Frontend | Alpine.js + Vanilla CSS |
| Infrastructure | Docker (Laravel Sail) |

## Quick Start

```bash
# 1. Clone and install
git clone <repo-url>
cd Agenticthon-
cp .env.example .env

# 2. Set your OpenAI API key in .env (required — powers Attacker/Defender/Policy Judge)
# OPENAI_API_KEY=your-key-here

# 3. Start with Docker Sail
docker compose up -d --build

# 4. Setup application
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test php artisan db:seed

# 5. Open http://localhost:8888
```

### Optional: Enable Guardrails (Phase 2)
```bash
docker compose --profile guardrails up -d
docker compose exec laravel.test php artisan config:clear
```

### Health Check
```bash
docker compose exec laravel.test php artisan arena:health
```

## Agent Roster

### 🗡️ AttackerAgent
- **10 attack techniques**: jailbreak, role-play, injection, obfuscation, translation bypass, and more
- **Adaptive intelligence**: tracks technique effectiveness, rotates blocked families, escalates on block

### 🛡️ DefenderAgent
- **3 policy profiles**: strict (0.3), moderate (0.6), permissive (0.85)
- **Programmatic + LLM reasoning**: hard thresholds enforced before LLM evaluation

### ⚖️ PolicyJudgeAgent
- **Turn scoring**: red_team_win, blue_team_win, draw, false_positive
- **OWASP mapping**: LLM01, LLM02, LLM04, LLM06, LLM08, LLM09

## Scenarios

| # | Category | Severity | Techniques |
|---|----------|----------|-----------|
| 1 | Jailbreak | CRITICAL | role_play_framing, authority_impersonation |
| 2 | Self-Harm | CRITICAL | hypothetical_framing, multi_turn_escalation |
| 3 | PII Leakage | CRITICAL | indirect_injection, obfuscation |
| 4 | Toxicity | HIGH | translation_bypass, token_smuggling |
| 5 | Prompt Injection | HIGH | direct_jailbreak, many_shot_priming |
| 6 | Model Spec Violation | MEDIUM | direct_jailbreak, hypothetical_framing |

## API Endpoints

| Route | Method | Description |
|-------|--------|-------------|
| `/` | GET | Landing page |
| `/duels` | GET | Arena UI — run duels |
| `/duels/{scenario}/run` | POST | Execute a duel |
| `/duels/{duel}/status` | GET | Live duel status |
| `/duels/{duel}/report` | GET | Full duel report |
| `/duels/history/all` | GET | Browse past duels |
| `/api/stats` | GET | Analytics dashboard data |

## Project Status

| Phase | Status | Notes |
|-------|--------|-------|
| Phase 1 — Foundation | Complete | Agents, scenarios, persistence, duel loop |
| Phase 2 — Defense Layer | Complete | NeMo and LLM Guard service integrations with safe-fail behavior |
| Phase 3 — Offense + Evaluation | Complete | Adaptive attacker logic and analytics dashboard service |
| Phase 4 — Landing Page | Complete | Marketing landing page at `/` and arena UI at `/duels` |

## License

MIT
