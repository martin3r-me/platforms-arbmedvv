<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transition from the former German table. The occasions are the ArbMedVV annex
        // catalog and are re-seedable, so we drop the old table rather than rename in place.
        Schema::dropIfExists('arbmedvv_anlaesse');

        Schema::create('arbmedvv_occasions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');

            // Taxonomy (ArbMedVV annex)
            $table->enum('section', ['hazardous_substances', 'biological_agents', 'physical_agents', 'other']);
            $table->enum('care_type', ['mandatory', 'offered', 'follow_up']);

            // Content
            $table->string('title');
            $table->text('trigger');                    // hazard/exposure trigger statement (verbatim)
            $table->string('threshold')->nullable();    // e.g. ">= 4 h/day", "85 dB(A)"
            $table->string('legal_basis')->nullable();  // e.g. "Annex Part 1 (2)"
            $table->text('description')->nullable();     // notes / hints

            $table->enum('status', ['active', 'archived'])->default('active');
            $table->unsignedInteger('position')->default(0);

            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status'], 'arbmedvv_occasions_team_status_idx');
            $table->index(['team_id', 'section'], 'arbmedvv_occasions_team_section_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arbmedvv_occasions');
    }
};
