# Red-Team Arena — Setup & Run Guide

A self-contained adversarial AI security platform.  
Red Team (LLM attacker) vs Blue Team (guardrails + defender) — scored by a Policy Judge.

---

## Prerequisites

| Tool | Version | Notes |
|------|---------|-------|
| Docker Desktop | 4.x+ | Must be running before step 1 |
| Git | any | For cloning |
| Groq API Key | — | Free at [console.groq.com](https://console.groq.com) |

> No PHP, Node, or Python needed on your machine — everything runs inside Docker.

---

## Step 1 — Clone the repo

```bash
git clone https://github.com/emadaqel/Agenticthon-.git
cd Agenticthon-
```

---

## Step 2 — Create your `.env` file

```bash
cp .env.example .env        # Linux / Mac
copy .env.example .env      # Windows CMD
```

Open `.env` and fill in your Groq API key:

```env
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Everything else can stay as the defaults for local development.

---

## Step 3 — Install PHP dependencies

The `vendor/` directory is needed before the Laravel container will start.  
Run this once using a temporary PHP container:

```bash
docker run --rm -v "$(pwd):/app" -w /app composer:2 install --no-interaction
```

**Windows PowerShell:**
```powershell
docker run --rm -v "${PWD}:/app" -w /app composer:2 install --no-interaction
```

---

## Step 4 — Generate the application key

```bash
docker run --rm -v "$(pwd):/app" -w /app php:8.2-cli php artisan key:generate --no-interaction
```

**Windows PowerShell:**
```powershell
docker run --rm -v "${PWD}:/app" -w /app php:8.2-cli php artisan key:generate --no-interaction
```

---

## Step 5 — Start all services

```bash
docker compose up -d --build
```

This starts 5 services:

| Container | Role | Port |
|-----------|------|------|
| `laravel.test` | Laravel app + queue worker | 80 |
| `pgsql` | PostgreSQL database | 5432 |
| `redis` | Cache + queue broker | 6379 |
| `nemo-guardrails` | NeMo-compatible input/output guard | 8000 |
| `llm-guard` | LLM Guard-compatible scanner | 8001 |

Wait ~30 seconds for all containers to finish starting.

---

## Step 6 — Run database migrations

```bash
docker compose exec laravel.test php artisan migrate --force
```

---

## Step 7 — Seed scenarios

```bash
docker compose exec laravel.test php artisan db:seed --class=ScenarioSeeder --force
```

This creates 6 built-in attack scenarios:

- **PII Leakage** — extract SSN / contact details from an HR assistant
- **Model Spec Violation** — force the model to break its JSON output schema
- **Prompt Injection** — embed hidden instructions inside user-supplied content
- **Jailbreak** — impersonate admin/developer to extract system prompts
- **Toxicity Bypass** — obfuscate or translate harmful content past filters
- **Self-Harm Probe** — academic/professional framing to elicit dangerous guidance

---

## Step 8 — Start the queue worker

Duels run asynchronously. Open a second terminal and start the worker:

```bash
docker compose exec laravel.test php artisan queue:work --sleep=3 --tries=1
```

Or run it detached (background):

```bash
docker compose exec -d laravel.test php artisan queue:work --sleep=3 --tries=1
```

---

## Step 9 — Open the app

```
http://localhost
```

The health banner at the top should show all 5 services as **ONLINE** within ~30 seconds.

---

## Running a Duel

1. Click the **Arena** tab (default)
2. Select a scenario from the left panel — full details appear inline
3. Set **Policy Profile** (`strict` / `moderate` / `permissive`) and **Max Turns**
4. Choose a **Target Model** and **Provider**
5. Click **Launch Duel**
6. Watch turns appear one by one in real-time (each turn ~2–3 seconds)
7. When complete, the summary panel appears with Attack Success Rate and Defense Effectiveness
8. Click **Export Report** for a full HTML security report

---

## Tabs Overview

| Tab | What it does |
|-----|-------------|
| **Arena** | Run live red-team duels |
| **Dashboard** | Aggregated stats, heatmaps, OWASP breakdown |
| **History** | All past duels with report export per row |
| **Compare** | Side-by-side model comparison under identical attack |
| **Scenarios** | View built-in scenarios + create custom ones |
| **Reports** | Open any past report by Duel ID |

All tab state (selected scenario, results, settings) persists across page reloads via `localStorage`.

---

## Demo Mode

Hit **Load Demo Data** in the bottom-left sidebar to seed 20 pre-computed duels so the Dashboard and History tabs have data immediately.

---

## PromptFoo Integration

Export any scenario as a `promptfooconfig.yaml` for external red-team testing:

```
GET /promptfoo/{scenario-id}/export
```

Or visit `/promptfoo` to see all scenarios with export links.

---

## Environment Variables Reference

```env
# Required
GROQ_API_KEY=gsk_...

# Optional — HuggingFace Router (for HF model comparison)
HF_API_KEY=hf_...

# Guardrail service URLs (pre-set for Docker Compose — do not change for local dev)
NEMO_GUARDRAILS_URL=http://nemo-guardrails:8000
LLM_GUARD_URL=http://llm-guard:8001

# Database (pre-set for Docker Compose)
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_DATABASE=redteam_arena
DB_USERNAME=sail
DB_PASSWORD=password

# Redis (pre-set for Docker Compose)
REDIS_HOST=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

---

## Stopping the Project

```bash
docker compose down
```

To also remove stored data (database volumes):

```bash
docker compose down -v
```

---

## Troubleshooting

**Health banner shows services OFFLINE**
- Wait 30 seconds after `docker compose up` — guardrail containers take time to build on first run
- Run `docker compose ps` to check container status
- Run `docker compose logs nemo-guardrails` or `docker compose logs llm-guard` to inspect errors

**Duel never starts / stays "running"**
- Make sure the queue worker is running (Step 8)
- Check `docker compose logs laravel.test` for PHP errors
- Verify `GROQ_API_KEY` is set in `.env`

**Database migration errors**
- Run `docker compose exec laravel.test php artisan migrate:fresh --seed` to reset completely

**Port 80 already in use**
- Set `APP_PORT=8080` in `.env` and access at `http://localhost:8080`
