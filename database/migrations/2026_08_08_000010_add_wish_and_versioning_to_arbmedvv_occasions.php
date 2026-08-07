<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Spalte 4 · ArbMedVV:
 *  #44 Wunschvorsorge (§5a) als eigenständige, ANHANGSUNABHÄNGIGE Kategorie
 *      → care_type um 'request' erweitern; section nullable (Wunsch braucht keinen Anhang-Teil).
 *  #45 Versionsstand/Gültigkeitsdatum für künftige ArbMedVV-Novellierungen.
 */
return new class extends Migration
{
    public function up(): void
    {
        // #44: Wunschvorsorge als care_type; section nullable (anhangsunabhängig).
        DB::statement("ALTER TABLE arbmedvv_occasions MODIFY care_type ENUM('mandatory','offered','follow_up','request') NOT NULL");
        DB::statement("ALTER TABLE arbmedvv_occasions MODIFY section ENUM('hazardous_substances','biological_agents','physical_agents','other') NULL");

        // #45: Versionierung.
        Schema::table('arbmedvv_occasions', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->date('valid_from')->nullable()->after('version');
            $table->date('valid_until')->nullable()->after('valid_from');       // null = aktuell gültig
            $table->string('regulation_label')->nullable()->after('valid_until'); // z.B. "ArbMedVV Stand 2019"
        });
    }

    public function down(): void
    {
        Schema::table('arbmedvv_occasions', function (Blueprint $table) {
            $table->dropColumn(['version', 'valid_from', 'valid_until', 'regulation_label']);
        });

        DB::statement("ALTER TABLE arbmedvv_occasions MODIFY section ENUM('hazardous_substances','biological_agents','physical_agents','other') NOT NULL");
        DB::statement("ALTER TABLE arbmedvv_occasions MODIFY care_type ENUM('mandatory','offered','follow_up') NOT NULL");
    }
};
