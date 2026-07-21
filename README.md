# Red-Team Arena

Red-Team Arena is an agentic AI security validation platform. It runs adversarial duels, imports versioned prompt corpora, traces model-to-tool attack paths, turns failures into reviewable remediation proposals, and produces evidence that can gate a release.

## What is implemented

- Red/blue multi-agent duels with strict, moderate, and permissive policy profiles.
- Versioned Promptfoo YAML or JSON corpus import from allow-listed GitHub sources.
- Reproducible corpus runs pinned to a SHA-256 content fingerprint.
- Per-case traces covering input scanning, target response, output scanning, defense, and judging.
- Structured findings with exploit chain, evidence, severity, and OWASP mapping.
- GPT-5.6 security-advisor proposals when configured, with a deterministic fallback.
- Mandatory human approval or rejection for every remediation proposal.
- Model/tool/data attack-path analysis with least-privilege and approval recommendations.
- A deterministic vulnerable-versus-remediated demo and CI security gate.
- Truthful evidence states: `effective`, `bypassed`, `not_triggered`, `unavailable`, and `not_evaluated`.

The optional Docker guardrail service is a lightweight, local rule engine exposing NeMo-compatible and LLM Guard-compatible HTTP surfaces. It does **not** bundle the upstream NeMo Guardrails or LLM Guard packages. Health responses expose `engine: arena-rules-v2` and `upstream_package: false` so reports do not overstate the control in use.

## Architecture

```text
GitHub corpus -> fingerprinted run -> input controls -> target model -> output controls
                                            |                              |
                                            +------ full case trace -------+
                                                              |
                                         findings -> advisor proposal -> human review
                                                              |
                                                  evidence report -> CI gate

Agent graph -> reachable sensitive paths -> risk score -> least-privilege controls
```

## Quick start

```bash
cp .env.example .env
docker compose up -d
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test php artisan db:seed
```

Open:

- `http://localhost/security` — integrated Security Workspace
- `http://localhost/duels` — adversarial duel arena
- `http://localhost/scenarios` — scenario management
- `http://localhost/promptfoo` — legacy Promptfoo export

To run the local compatible guardrail adapters:

```bash
docker compose up -d nemo-guardrails llm-guard
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan arena:health
```

Inside Docker, both adapter URLs use port `8000`; the LLM Guard-compatible host port is `8001`.

## Security workflow

Import a public corpus through the workspace or API:

```http
POST /api/corpora
Content-Type: application/json

{
  "name": "OWASP agent prompts",
  "source_url": "https://raw.githubusercontent.com/org/repo/main/prompts.yaml",
  "source_ref": "main"
}
```

Run it against an existing scenario, materialize findings, request a remediation proposal, then approve or reject it explicitly. The API never applies a proposed control automatically.

Useful commands:

```bash
php artisan arena:demo-security --json
php artisan arena:security-gate <run-uuid> --json
php artisan test
```

The GitHub Actions workflow runs the Laravel suite plus the deterministic proof. Runtime thresholds are configured with:

```dotenv
SECURITY_CI_MAX_FAILED_CASES=0
SECURITY_CI_MAX_CRITICAL_FINDINGS=0
```

## GPT-5.6 advisor

The advisor is optional. Add an OpenAI API key and keep proposals subject to review:

```dotenv
OPENAI_API_KEY=
SECURITY_ADVISOR_ENABLED=true
SECURITY_ADVISOR_PROVIDER=openai
SECURITY_ADVISOR_MODEL=gpt-5.6-sol
```

Without a configured key, the platform returns a deterministic remediation proposal and marks its provenance accordingly.

## Core API

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/api/security/demo` | Deterministic before/after security proof |
| `GET/POST` | `/api/corpora` | List or import corpora |
| `POST` | `/api/corpora/{corpus}/runs` | Execute a fingerprinted corpus run |
| `POST` | `/api/corpus-runs/{run}/findings` | Create structured findings |
| `POST` | `/api/findings/{finding}/remediation` | Generate a pending proposal |
| `PATCH` | `/api/remediations/{proposal}/review` | Approve or reject once |
| `POST` | `/api/attack-surfaces/analyze` | Analyze an agent component graph |
| `GET` | `/api/corpus-runs/{run}/security-report` | Export traceable control evidence |
| `GET` | `/api/corpus-runs/{run}/gate` | Evaluate release thresholds |

Mutation endpoints are CSRF-protected and rate-limited. Public deployments should add application authentication and organization-level authorization before exposure.

## Stack

Laravel 12, PHP 8.2+, PostgreSQL, Redis, Prism PHP, Groq/OpenAI/Hugging Face providers, and Docker Compose.

## License

MIT
