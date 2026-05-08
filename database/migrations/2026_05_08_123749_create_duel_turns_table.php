<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duel_turns', function (Blueprint $table) {
            $table->id();
            $table->uuid('duel_id');
            $table->uuid('scenario_id');
            $table->integer('turn');
            $table->string('attacker_technique')->nullable();
            $table->text('adversarial_prompt')->nullable();
            $table->json('guardrail_input_result')->nullable();
            $table->text('model_response')->nullable();
            $table->json('guardrail_output_result')->nullable();
            $table->string('defender_verdict')->nullable();
            $table->string('judge_outcome')->nullable();
            $table->string('owasp_category')->nullable();
            $table->float('risk_score_input')->nullable();
            $table->float('risk_score_output')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duel_turns');
    }
};
