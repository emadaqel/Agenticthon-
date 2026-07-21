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

The optional Docker guardrail service is a lightweight, local rule engine exposing NeMo-compatible and LLM Guard-compatible HTTP surfaces. It does **not** bundle the upstream NeMo Guardrails or LLM Guard packages. Health responses expose `engine: arena-rules-v2` and `upstream_package: false`.

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

## Open and access the project

### Requirements

- Docker Desktop with Docker Compose enabled.
- Git.
- Free host ports `80`, `5432`, `6379`, `8000`, `8001`, and `5173`.
- A Groq API key for live duels. OpenAI and Hugging Face keys are optional.

From PowerShell, clone the repository and switch to the feature branch:

```powershell
git clone https://github.com/emadaqel/Agenticthon-.git
Set-Location Agenticthon-
git switch codex/sentinel-security-platform
Copy-Item .env.example .env
```

Add API keys to `.env` as needed:

```dotenv
GROQ_API_KEY=
OPENAI_API_KEY=
HUGGINGFACE_API_KEY=
```

Start and initialize the application:

```powershell
docker compose up -d --build
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
```

The first build can take several minutes. Confirm the containers with:

```powershell
docker compose ps
```

Open these addresses in a browser:

| Address | Purpose |
|---|---|
| `http://localhost/security` | Integrated Security Workspace and reproducible before/after proof |
| `http://localhost/duels` | Adversarial duel arena |
| `http://localhost/scenarios` | Scenario management |
| `http://localhost/promptfoo` | Promptfoo export interface |
| `http://localhost/api/security/demo` | Machine-readable deterministic proof |
| `http://localhost/api/health` | Runtime service health |

If port 80 is occupied, set `APP_PORT=8080` in `.env` and use `http://localhost:8080`.

## Reproduce the security results

The deterministic proof does not require any external model key:

```powershell
docker compose run --rm laravel.test php artisan arena:demo-security --json
```

Expected result:

- Vulnerable version: two failed adversarial cases.
- Remediated version: zero failed cases.
- Benign pass rate: preserved at 100%.
- A stable `corpus_hash` identifying the exact test input.

To reproduce it visually:

1. Open `http://localhost/security`.
2. Confirm the Before/After panel reports `2` failures before and `0` afterward.
3. Confirm **Benign preserved** reports `YES`.
4. Select **Analyze sample path** to expose the untrusted-input-to-sensitive-data path.
5. Select **Analyze with controls** to compare the reduced risk and recommended controls.

To exercise a real model, add `GROQ_API_KEY`, open `/duels`, select a scenario, policy, and model, then run the duel. GPT-5.6 remediation generation additionally needs `OPENAI_API_KEY`; without it the review workflow uses its deterministic fallback.

## One-command testing workflow

The repository includes [scripts/test-workflow.ps1](scripts/test-workflow.ps1). It starts the stack, applies migrations, runs Composer validation and audit, executes every automated test, validates the deterministic security proof, and smoke-tests the live HTTP endpoints.

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\test-workflow.ps1
```

Use `-BuildImages` after changing a Dockerfile or container dependency:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\test-workflow.ps1 -BuildImages
```

Detailed expected results, manual test cases, and troubleshooting are in [docs/TESTING.md](docs/TESTING.md).

## Manual setup commands

```bash
cp .env.example .env
docker compose up -d
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test php artisan db:seed
```

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

The **AI security gate** GitHub Actions workflow runs for pull requests and pushes to `main`, and can be started manually from the Actions tab. Runtime thresholds are configured with:

```dotenv
SECURITY_CI_MAX_FAILED_CASES=0
SECURITY_CI_MAX_CRITICAL_FINDINGS=0
```

## GPT-5.6 advisor

The advisor is optional:

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
