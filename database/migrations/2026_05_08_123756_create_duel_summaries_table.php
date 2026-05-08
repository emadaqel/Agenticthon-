<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duel_summaries', function (Blueprint $table) {
            $table->uuid('duel_id')->primary();
            $table->uuid('scenario_id');
            $table->string('target_model');
            $table->string('policy_profile');
            $table->integer('total_turns');
            $table->integer('red_team_wins')->default(0);
            $table->integer('blue_team_wins')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('false_positives')->default(0);
            $table->float('attack_success_rate')->default(0.0);
            $table->float('defense_effectiveness')->default(0.0);
            $table->json('vulnerabilities_found')->nullable();
            $table->json('owasp_categories')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duel_summaries');
    }
};
