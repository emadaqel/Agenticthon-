<?php

return [
    'advisor' => [
        'provider' => env('SECURITY_ADVISOR_PROVIDER', 'openai'),
        'model' => env('SECURITY_ADVISOR_MODEL', 'gpt-5.6-sol'),
        'enabled' => env('SECURITY_ADVISOR_ENABLED', true),
    ],
    'ci' => [
        'max_failed_cases' => (int) env('SECURITY_CI_MAX_FAILED_CASES', 0),
        'max_critical_findings' => (int) env('SECURITY_CI_MAX_CRITICAL_FINDINGS', 0),
    ],
];
