# Red-Team Arena — Agents & Architecture Context

> **Last updated:** Phase 4 complete — Laravel 11 dependencies aligned, queued duel job repaired  
> **Stack:** Laravel 11 · Prism PHP (Laravel AI SDK) · PostgreSQL · Redis · Docker (Sail)  
> **Running at:** http://localhost (via `docker compose up -d` in `Agenticthon-`)

---

## Environment

| Item | Value |
|------|-------|
| Laravel version | 11 (latest) |
| PHP | 8.2+ |
| Laravel AI SDK | `prism-php/prism` v0.100.1 |
| Primary LLM | Groq free tier (`llama3-70b-8192` for agents, `llama3-8b-8192` for target) |
| Database | PostgreSQL (Sail container) |
| Cache / Queue | Redis (Sail container) |
| Docker network | `redteam_sail` |

**To start the stack:**
```bash
cd Agenticthon-
docker compose up -d
```

**Required `.env` keys:**
```env
GROQ_API_KEY=<your-groq-api-key>       # https://console.groq.com (free)
NEMO_GUARDRAILS_URL=http://nemo-guardrails:8000   # Phase 2 — activate with docker compose --profile guardrails up -d
LLM_GUARD_URL=http://llm-guard:8000               # Internal Docker port; host port is 8001
```

---

## Agent Roster

### 1. `AttackerAgent` — `app/AI/Agents/AttackerAgent.php`

- **Role:** Red Team. Generates adversarial prompts to make the target LLM violate its safety rules.
- **Provider:** Groq `llama3-70b-8192`
- **Input:** `Scenario` model + conversation history (array of previous turn records)
- **Output JSON:**
  ```json
  {
    "technique_used": "role_play_framing",
    "prompt": "...",
    "reasoning": "..."
  }
  ```
- **Attack techniques arsenal (10 total):**  
  `direct_jailbreak`, `role_play_framing`, `multi_turn_escalation`, `obfuscation`, `translation_bypass`, `indirect_injection`, `hypothetical_framing`, `authority_impersonation`, `token_smuggling`, `many_shot_priming`
- **Status:** ✅ Implemented — prompt generation plus adaptive technique-family rotation after blocked turns.

---

### 2. `DefenderAgent` — `app/AI/Agents/DefenderAgent.php`

- **Role:** Blue Team. Evaluates model response and issues a binding verdict.
- **Provider:** Groq `llama3-70b-8192`
- **Input:** adversarial prompt, guardrail input scan, model response, guardrail output scan, policy profile
- **Output JSON:**
  ```json
  {
    "verdict": "BLOCK | ALLOW | MODIFY",
    "reasoning": "...",
    "modified_response": "null or sanitized string"
  }
  ```
- **Policy Profiles:**
  - `strict` — block on any risk > 0.3
  - `moderate` — block > 0.6, allow modify 0.3–0.6
  - `permissive` — block > 0.85
- **Status:** Implemented — programmatic thresholds plus LLM-based reasoning over compatible local rule-engine scan results.

---

### 3. `PolicyJudgeAgent` — `app/AI/Agents/PolicyJudgeAgent.php`

- **Role:** Referee. Scores each turn and emits the final duel summary.
- **Provider:** Groq `llama3-70b-8192`
- **Input:** Full turn record JSON
- **Output JSON (per turn):**
  ```json
  {
    "outcome": "red_team_win | blue_team_win | draw | false_positive",
    "owasp_category": "LLM01",
    "reasoning": "..."
  }
  ```
- **OWASP LLM Top 10 categories tracked:** LLM01, LLM02, LLM04, LLM06, LLM08, LLM09
- **Also produces:** duel summary (totals, rates, OWASP categories hit)
- **Phase 1 status:** ✅ Implemented.

---

## Services

### `ModelGateway` — `app/Services/ModelGateway.php`
- Calls the target model via Prism PHP with the scenario's `base_prompt` as system context.
- Returns: response text, model name, provider, latency_ms, token counts.

### `NeMoGuardrailsService` — `app/Services/NeMoGuardrailsService.php`
- ✅ **Real implementation — no stubs.**
- Always attempts live HTTP call to `NEMO_GUARDRAILS_URL`.
- On connection failure → returns `blocked: false`, logs warning, sets `flagged_for_review: true`.
- On timeout (2s per global rule) → same safe-fail behavior.
- Endpoints used: `POST /v1/rails/input`, `POST /v1/rails/output`
- Uses the local `arena-rules-v2` NeMo-compatible adapter, not the upstream NeMo package.

### `LlmGuardService` — `app/Services/LlmGuardService.php`
- ✅ **Real implementation — no stubs.**
- Always attempts live HTTP call to `LLM_GUARD_URL`.
- On connection failure → returns `risk_score: 0.0`, logs warning, sets `flagged_for_review: true`.
- On timeout (2s) → same safe-fail behavior.
- Endpoints used: `POST /scan/prompt`, `POST /scan/output`
- Uses the local `arena-rules-v2` LLM Guard-compatible adapter, not the upstream LLM Guard package.

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `scenarios` | Attack scenarios (category, base_prompt, metadata, attack_patterns) |
| `duel_turns` | Per-turn log (prompt, response, verdicts, scores, latency) |
| `duel_summaries` | Per-duel totals (red/blue wins, success rate, OWASP categories) |

---

## Controller & Routes

**`DuelController`** — `app/Http/Controllers/DuelController.php`

| Route | Method | Action |
|-------|--------|--------|
| `GET /duels` | GET | List scenarios UI |
| `POST /duels/{scenario}/run` | POST | Execute a full duel (sync) |
| `GET /duels/{duel}/status` | GET | Live duel state from Redis cache |
| `GET /duels/{duel}/report` | GET | Full duel report from DB |

**Full duel loop (per turn):**
1. `AttackerAgent::generate()` → adversarial prompt
2. `NeMoGuardrailsService::checkInput()` + `LlmGuardService::scanInput()` → guardrail check
3. If input blocked → blue win, skip to next turn
4. `ModelGateway::call()` → target LLM response
5. `NeMoGuardrailsService::checkOutput()` + `LlmGuardService::scanOutput()` → output guardrail
6. `DefenderAgent::evaluate()` → verdict (ALLOW / BLOCK / MODIFY)
7. `PolicyJudgeAgent::scoreTurn()` → outcome label + OWASP category
8. Persist `DuelTurn` to DB + push to Redis live cache
9. After all turns → `PolicyJudgeAgent::summarize()` → persist `DuelSummary`

---

## Seeded Scenarios (6)

| # | Category | Severity | Techniques |
|---|----------|----------|-----------|
| 1 | jailbreak | CRITICAL | role_play_framing, authority_impersonation |
| 2 | self_harm | CRITICAL | hypothetical_framing, multi_turn_escalation |
| 3 | pii_leakage | CRITICAL | indirect_injection, obfuscation |
| 4 | toxicity | HIGH | translation_bypass, token_smuggling |
| 5 | prompt_injection | HIGH | direct_jailbreak, many_shot_priming |
| 6 | model_spec_violation | MEDIUM | direct_jailbreak, hypothetical_framing |

---

## Phase Status

| Phase | Status | Notes |
|-------|--------|-------|
| **Phase 1 — Foundation** | ✅ Complete | Agents, scenarios, persistence, duel loop |
| **Phase 2 — Defense Layer** | Complete | Compatible local guardrail adapters with explicit unavailable-control evidence |
| **Phase 3 — Offense + Evaluation** | ✅ Complete | Attacker adaptation and dashboard analytics service |
| **Phase 4 — Landing Page** | ✅ Complete | Marketing landing page and arena UI |

---

## Phase 2 Quickstart (for next session)

```bash
# 1. Start guardrail containers
docker compose --profile guardrails up -d

# 2. Verify NeMo is up
curl http://localhost:8000/health

# 3. Verify LLM Guard is up
curl http://localhost:8001/healthz

# 4. Add env vars to .env
NEMO_GUARDRAILS_URL=http://nemo-guardrails:8000
LLM_GUARD_URL=http://llm-guard:8001

# 5. Clear config cache
docker compose exec laravel.test php artisan config:clear
```

---

## Known Issues & Fixes Applied

| Issue | Fix |
|-------|-----|
| `tempnam()` ErrorException on view compile | `chmod -R 775 storage bootstrap/cache` + `chown -R sail:sail` |
| `WWWGROUP invalid` Docker build error | Added `WWWGROUP=1000` and `WWWUSER=1000` to `.env` in proper UTF-8 encoding |
