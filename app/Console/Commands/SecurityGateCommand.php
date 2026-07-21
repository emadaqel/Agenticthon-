<?php

namespace App\Console\Commands;

use App\Models\PromptCorpusRun;
use App\Services\SecurityGateService;
use Illuminate\Console\Command;

class SecurityGateCommand extends Command
{
    protected $signature = 'arena:security-gate {run? : Corpus run UUID} {--json : Emit machine-readable output}';
    protected $description = 'Fail CI when a reproducible corpus run violates security thresholds';

    public function handle(SecurityGateService $gate): int
    {
        $run = $this->argument('run')
            ? PromptCorpusRun::find($this->argument('run'))
            : PromptCorpusRun::where('status', 'complete')->latest()->first();

        if (! $run) {
            $this->error('No completed prompt corpus run was found.');
            return self::FAILURE;
        }

        $result = $gate->evaluate($run);
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info("Security gate: {$result['status']}");
            foreach ($result['violations'] as $violation) {
                $this->error($violation);
            }
        }

        return $result['status'] === 'pass' ? self::SUCCESS : self::FAILURE;
    }
}
