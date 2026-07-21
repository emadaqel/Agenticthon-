<?php

namespace App\Console\Commands;

use App\Services\DemoSecurityService;
use Illuminate\Console\Command;

class DemoSecurityCommand extends Command
{
    protected $signature = 'arena:demo-security {--json : Emit machine-readable evidence}';
    protected $description = 'Run the deterministic vulnerable-versus-remediated agent demonstration';

    public function handle(DemoSecurityService $demo): int
    {
        $result = $demo->compare();
        $this->line($this->option('json') ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : "Before failures: {$result['before']['failed']} | After failures: {$result['after']['failed']}");

        return $result['before']['failed'] > 0 && $result['after']['failed'] === 0 && $result['improvement']['benign_pass_rate_preserved']
            ? self::SUCCESS
            : self::FAILURE;
    }
}
