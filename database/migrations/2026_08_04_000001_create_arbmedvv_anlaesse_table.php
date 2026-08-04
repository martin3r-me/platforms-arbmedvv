<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arbmedvv_anlaesse', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');

            // Taxonomie (Anhang ArbMedVV)
            $table->enum('teil', ['gefahrstoffe', 'biostoffe', 'physikalisch', 'sonstige']);
            $table->enum('vorsorgeart', ['pflicht', 'angebot', 'nachgehend']);

            // Inhalt
            $table->string('titel');
            $table->text('ausloeser');                       // Gefährdungs-/Expositionstatbestand (Wortlaut)
            $table->string('grenzwert')->nullable();         // z.B. "≥ 4 Std./Tag", "85 dB(A)"
            $table->string('rechtsgrundlage')->nullable();   // z.B. "Anhang Teil 1 (2)"
            $table->text('beschreibung')->nullable();        // Hinweise/Notizen

            $table->enum('status', ['aktiv', 'archiviert'])->default('aktiv');
            $table->unsignedInteger('position')->default(0);

            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status'], 'arbmedvv_anlaesse_team_status_idx');
            $table->index(['team_id', 'teil'], 'arbmedvv_anlaesse_team_teil_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arbmedvv_anlaesse');
    }
};
