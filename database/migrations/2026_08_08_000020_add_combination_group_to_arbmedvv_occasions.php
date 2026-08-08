<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbmedvv_occasions', function (Blueprint $table) {
            // Vermengungs-/Exklusivitätsgruppe (orthogonal zu care_type). ArbMedVV = ausschließlich
            // Vorsorge → bestehende Anlässe werden auf "vorsorge" gesetzt.
            $table->string('combination_group')->nullable()->after('care_type');
        });

        DB::table('arbmedvv_occasions')->whereNull('combination_group')->update(['combination_group' => 'vorsorge']);
    }

    public function down(): void
    {
        Schema::table('arbmedvv_occasions', function (Blueprint $table) {
            $table->dropColumn('combination_group');
        });
    }
};
