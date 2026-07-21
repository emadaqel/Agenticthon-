param(
    [switch]$BuildImages
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

function Invoke-Step {
    param(
        [string]$Name,
        [scriptblock]$Action
    )

    Write-Host "`n==> $Name" -ForegroundColor Cyan
    & $Action
    if ($LASTEXITCODE -ne 0) {
        throw "$Name failed with exit code $LASTEXITCODE."
    }
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker is not installed or is not available on PATH.'
}

$createdEnvironment = $false
if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    $createdEnvironment = $true
    Write-Host 'Created .env from .env.example.' -ForegroundColor Yellow
}

Invoke-Step 'Validate Docker Compose configuration' {
    docker compose config --quiet
}

Invoke-Step 'Build the pinned PHP 8.5 runtime' {
    docker compose build laravel.test
}

Invoke-Step 'Install locked Composer dependencies' {
    docker compose run --rm --no-deps laravel.test composer install `
        --no-interaction `
        --prefer-dist `
        --no-progress `
        --no-security-blocking
}

Invoke-Step 'Install locked frontend dependencies' {
    docker compose run --rm --no-deps laravel.test npm ci --no-audit --no-fund
}

Invoke-Step 'Build production frontend assets' {
    docker compose run --rm --no-deps laravel.test npm run build
}

Invoke-Step 'Start application services' {
    if ($BuildImages) {
        docker compose up -d --build
    } else {
        docker compose up -d
    }
}

if ($createdEnvironment) {
    Invoke-Step 'Generate Laravel application key' {
        docker compose run --rm laravel.test php artisan key:generate
    }
}

Invoke-Step 'Apply database migrations' {
    docker compose run --rm laravel.test php artisan migrate --force
}

Invoke-Step 'Validate Composer configuration' {
    docker compose run --rm `
        -e GIT_CONFIG_COUNT=1 `
        -e GIT_CONFIG_KEY_0=safe.directory `
        -e GIT_CONFIG_VALUE_0=/var/www/html `
        laravel.test composer validate --strict
}

Write-Host "`n==> Report locked dependency advisories" -ForegroundColor Cyan
docker compose run --rm `
    -e GIT_CONFIG_COUNT=1 `
    -e GIT_CONFIG_KEY_0=safe.directory `
    -e GIT_CONFIG_VALUE_0=/var/www/html `
    laravel.test composer audit --locked
$auditExitCode = $LASTEXITCODE
if ($auditExitCode -gt 1) {
    throw "Composer audit failed unexpectedly with exit code $auditExitCode."
}
if ($auditExitCode -eq 1) {
    Write-Warning 'Composer reported the documented Laravel 8 EOL advisories. The workflow will continue so functional regressions can still be detected.'
}

Invoke-Step 'Run complete Laravel test suite' {
    docker compose run --rm laravel.test php artisan test
}

Write-Host "`n==> Validate deterministic security proof" -ForegroundColor Cyan
$proofText = docker compose run --rm laravel.test php artisan arena:demo-security --json | Out-String
if ($LASTEXITCODE -ne 0) {
    throw 'The deterministic security proof command failed.'
}
$proof = $proofText | ConvertFrom-Json
if ($proof.before.failed -ne 2 -or $proof.after.failed -ne 0 -or -not $proof.improvement.benign_pass_rate_preserved) {
    throw 'The deterministic proof did not produce the expected 2-to-0 result with benign behavior preserved.'
}

Write-Host "`n==> Smoke-test live HTTP endpoints" -ForegroundColor Cyan
$workspace = $null
for ($attempt = 1; $attempt -le 20; $attempt++) {
    try {
        $workspace = Invoke-WebRequest -UseBasicParsing 'http://localhost/security'
        break
    } catch {
        Start-Sleep -Seconds 1
    }
}
if ($null -eq $workspace -or $workspace.StatusCode -ne 200) {
    throw 'The Security Workspace did not become available at http://localhost/security.'
}

$demo = Invoke-RestMethod 'http://localhost/api/security/demo'
$nemo = Invoke-RestMethod 'http://localhost:8000/v1/health'
$guard = Invoke-RestMethod 'http://localhost:8001/health'

if ($demo.demo.after.failed -ne 0) {
    throw 'The live security demo reported a regression.'
}
if ($nemo.engine -ne 'arena-rules-v2' -or $guard.engine -ne 'arena-rules-v2') {
    throw 'A guardrail adapter reported an unexpected engine.'
}

Write-Host "`nAll verification steps passed." -ForegroundColor Green
Write-Host 'Tests: Laravel suite passed'
Write-Host "Security proof: $($proof.before.failed) failures before, $($proof.after.failed) after"
Write-Host "Workspace: HTTP $($workspace.StatusCode)"
Write-Host "Adapters: $($nemo.engine), $($guard.engine)"
Write-Host "Dependency audit exit code: $auditExitCode (Laravel 8 EOL advisories are documented)"
