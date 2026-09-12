<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anlass ↔ Verfahren · Ausbaustufe 1 — verbindet den ArbMedVV-Anlass mit dem medizinischen
 * Verfahren (examinations). Beziehung ist N:1: mehrere Anlässe → EIN Verfahren
 * (Live-Beleg: "Lärm 85 dB(A)" + "Lärm 80 dB(A)" → ein Verfahren G20).
 *
 * - catalog_code: kanonischer Master-Schlüssel des Anlasses (sync-stabil, team-übergreifend).
 * - examination_id: LOSE Referenz auf examinations.id — bewusst KEIN harter FK, Cross-Modul-
 *   Kopplung bleibt lose (wie morphMap / patient_id anderswo). nullable: nicht jeder Anlass hat
 *   (heute) ein hinterlegtes Verfahren — nur 5 von 65 haben aktuell ein Gegenstück.
 *
 * Rein additiv, beide Spalten nullable → keine Auswirkung auf Bestandsdaten (Backfill in Phase 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbmedvv_occasions', function (Blueprint $table) {
            $table->string('catalog_code', 64)->nullable()->after('uuid');
            $table->unsignedBigInteger('examination_id')->nullable()->after('catalog_code');

            $table->index(['team_id', 'catalog_code'], 'arbmedvv_occasions_team_catalog_code_idx');
            $table->index('examination_id', 'arbmedvv_occasions_examination_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('arbmedvv_occasions', function (Blueprint $table) {
            $table->dropIndex('arbmedvv_occasions_team_catalog_code_idx');
            $table->dropIndex('arbmedvv_occasions_examination_id_idx');
            $table->dropColumn(['catalog_code', 'examination_id']);
        });
    }
};
