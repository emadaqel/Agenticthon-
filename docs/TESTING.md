# Testing workflow

This guide reproduces the core Red-Team Arena result without relying on an external model and explains how to test the model-backed workflows.

## Automated local verification

From the repository root in PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\test-workflow.ps1
```

Add `-BuildImages` after a Dockerfile or container dependency changes:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\test-workflow.ps1 -BuildImages
```

The script stops immediately when a step fails. It verifies:

1. Docker Compose configuration.
2. PostgreSQL, Redis, Laravel, and both compatible guardrail adapters start.
3. Composer and npm lock files install, and the production frontend bundle builds.
4. Database migrations apply successfully.
5. `composer.json` and `composer.lock` are valid, and the known Laravel 8 EOL advisories are printed explicitly.
6. The complete Laravel test suite passes.
7. The deterministic proof moves from two failures to zero.
8. Benign behavior remains successful.
9. `/security` responds with HTTP 200.
10. Both guardrail health endpoints identify `arena-rules-v2`.

## Expected automated result

The current baseline is 20 passing tests with 89 assertions. It includes direct coverage of the OpenAI Responses API adapter, OpenAI-compatible chat completions, provider errors, the security workspace, corpus imports, evidence generation, remediation review, and attack-path analysis.

No functional test failures or application-runtime PHP compatibility warnings are expected. Composer can print PHP 8.5 deprecation notices while its own process autoloads Laravel 8 helper functions; those notices are an expected consequence of the unsupported version pairing and do not appear during the application boot or test suite. Composer audit is expected to return exit code `1` and report three Laravel framework advisories (one high severity and two medium severity) because this branch intentionally pins the end-of-life Laravel 8.83.29 release. The workflow reports that result and continues with the functional gates.

## Manual test cases

### 1. Deterministic remediation proof

1. Open `http://localhost/security`.
2. Verify **Before failures** is `2`.
3. Verify **After failures** is `0`.
4. Verify **Benign preserved** is `YES`.
5. Compare the response text for the two adversarial cases before and after remediation.

Expected: secrets or hidden configuration appear only in the intentionally vulnerable responses. The remediated responses refuse both attacks, while the quarterly-summary request still succeeds.

### 2. Agent attack-path analysis

1. In the Security Workspace, select **Analyze sample path**.
2. Confirm the result identifies a path from User input through Agent model and Admin tool to Customer vault.
3. Select **Analyze with controls**.
4. Compare the risk score and recommendations.

Expected: the exposed graph reports a sensitive path and recommends trust-boundary, approval, or least-privilege controls. The controlled graph has a lower risk score.

### 3. Public corpus import

1. Enter a name and a public GitHub raw or blob URL containing Promptfoo YAML or supported JSON.
2. Select **Import and fingerprint**.
3. Confirm the case count, SHA-256 fingerprint, and revision information appear.

Expected: non-GitHub URLs are rejected. Reimporting changed content updates the fingerprint and cases transactionally.

### 4. Live duel

1. Add `GROQ_API_KEY` to `.env`.
2. Run `docker compose exec laravel.test php artisan config:clear`.
3. Open `http://localhost/duels`.
4. Select a scenario, target model, policy profile, and turn count.
5. Run the duel and inspect the report.

Expected: each turn records input controls, target response, output controls, defender verdict, judge outcome, risks, latency, and OWASP category.

### 5. Remediation review

Run a public corpus against a configured scenario, materialize its failed cases as findings, and request remediation.

Expected: every proposal starts as `pending_review`. It can transition once to `approved` or `rejected`; it is never automatically applied.

## GitHub Actions

The **AI security gate** workflow runs for pull requests, pushes to `main`, or manual dispatch from the Actions tab. It uses PHP 8.5, performs Composer validation and an informational audit, then requires the full test suite, route discovery, and deterministic proof to pass.

## Troubleshooting

- Use `docker compose ps` to inspect container state.
- Use `docker compose logs laravel.test` for application startup failures.
- Use `docker compose logs nemo-guardrails llm-guard` for adapter failures.
- If port 80 is occupied, set `APP_PORT=8080` and use `http://localhost:8080`.
- If configuration changes are not visible, run `docker compose exec laravel.test php artisan config:clear`.
- Live duels require a model-provider key; the deterministic proof and automated test suite do not.
- The first `-BuildImages` run can take about 10 minutes while the repository-owned PHP 8.5 image is assembled. Later builds should reuse cached layers.
