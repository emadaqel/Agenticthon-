<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_corpora', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('source_url');
            $table->string('source_ref')->nullable();
            $table->string('source_sha')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('prompt_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prompt_corpus_id');
            $table->string('external_id');
            $table->string('category')->nullable();
            $table->text('prompt');
            $table->text('expected_policy')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('prompt_corpus_id')->references('id')->on('prompt_corpora')->cascadeOnDelete();
            $table->unique(['prompt_corpus_id', 'external_id']);
        });

        Schema::create('prompt_corpus_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prompt_corpus_id');
            $table->uuid('scenario_id');
            $table->string('corpus_hash', 64);
            $table->string('target_model');
            $table->string('provider');
            $table->string('policy_profile');
            $table->string('status')->default('running');
            $table->unsignedInteger('total_cases')->default(0);
            $table->unsignedInteger('passed_cases')->default(0);
            $table->unsignedInteger('failed_cases')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('prompt_corpus_id')->references('id')->on('prompt_corpora')->cascadeOnDelete();
            $table->foreign('scenario_id')->references('id')->on('scenarios')->cascadeOnDelete();
        });

        Schema::create('prompt_case_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('prompt_corpus_run_id');
            $table->uuid('prompt_case_id');
            $table->boolean('passed');
            $table->string('outcome');
            $table->string('defender_verdict')->nullable();
            $table->float('risk_score_input')->default(0);
            $table->float('risk_score_output')->default(0);
            $table->text('model_response')->nullable();
            $table->json('trace');
            $table->timestamps();

            $table->foreign('prompt_corpus_run_id')->references('id')->on('prompt_corpus_runs')->cascadeOnDelete();
            $table->foreign('prompt_case_id')->references('id')->on('prompt_cases')->cascadeOnDelete();
            $table->unique(['prompt_corpus_run_id', 'prompt_case_id']);
        });

        Schema::create('security_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prompt_corpus_run_id')->nullable();
            $table->unsignedBigInteger('prompt_case_result_id')->nullable();
            $table->string('title');
            $table->string('category');
            $table->string('severity');
            $table->string('status')->default('open');
            $table->text('description');
            $table->json('exploit_chain');
            $table->json('evidence');
            $table->timestamps();

            $table->foreign('prompt_corpus_run_id')->references('id')->on('prompt_corpus_runs')->cascadeOnDelete();
            $table->foreign('prompt_case_result_id')->references('id')->on('prompt_case_results')->cascadeOnDelete();
            $table->unique('prompt_case_result_id');
        });

        Schema::create('remediation_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('security_finding_id');
            $table->string('status')->default('pending_review');
            $table->string('advisor_provider');
            $table->string('advisor_model');
            $table->string('generation_mode');
            $table->text('summary');
            $table->json('proposed_changes');
            $table->json('regression_tests');
            $table->text('reviewer_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('security_finding_id')->references('id')->on('security_findings')->cascadeOnDelete();
        });

        Schema::create('attack_surface_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->json('components');
            $table->json('connections');
            $table->json('attack_paths');
            $table->json('recommendations');
            $table->float('risk_score')->default(0);
            $table->string('status')->default('complete');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_surface_assessments');
        Schema::dropIfExists('remediation_proposals');
        Schema::dropIfExists('security_findings');
        Schema::dropIfExists('prompt_case_results');
        Schema::dropIfExists('prompt_corpus_runs');
        Schema::dropIfExists('prompt_cases');
        Schema::dropIfExists('prompt_corpora');
    }
};
