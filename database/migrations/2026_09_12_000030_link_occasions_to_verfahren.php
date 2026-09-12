<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Occasion → Verfahren · Ausbaustufe 3 — setzt examination_id (N:1) für die Anlässe, die
 * einem DGUV-Grundsatz zugeordnet sind. Titel-/Sektions-basiert (team-robust), inkl. der
 * N:1-Partner: z.B. beide Lärm-Anlässe → G20, beide Vibrations-Anlässe → G46, alle
 * biologischen Anlässe → G42 (Infektionsgefährdung).
 *
 * FACHLICH von Dr. Erren zu bestätigen/verfeinern. Additiv & reversibel (down → NULL).
 * Nur nicht-belegte Links werden gesetzt (vorhandene bleiben unberührt).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Grundsatz-Nummer => Liste von [Spalte, Operator, Wert]-Bedingungen (OR-verknüpft).
        $rules = [
            'G 20' => [['title', 'like', 'Lärm%']],
            'G 24' => [['title', 'like', 'Feuchtarbeit%']],
            'G 26' => [['title', 'like', 'Atemschutzgeräte%']],
            'G 37' => [['title', 'like', 'Bildschirm%']],
            'G 30' => [['title', 'like', '%Hitzebelastung%']],
            'G 46' => [['title', 'like', 'Vibrationen%'], ['title', 'like', '%Muskel-Skelett%']],
            'G 35' => [['title', 'like', 'Tropen%'], ['title', 'like', 'Abschlussvorsorge nach Auslands%']],
            'G 42' => [['section', '=', 'biological_agents']],
        ];

        $teamIds = DB::table('arbmedvv_occasions')->distinct()->pluck('team_id');

        foreach ($teamIds as $teamId) {
            foreach ($rules as $number => $conditions) {
                $examId = DB::table('examinations')
                    ->where('team_id', $teamId)
                    ->whereRaw("REPLACE(number, ' ', '') = ?", [str_replace(' ', '', $number)])
                    ->value('id');

                if (!$examId) {
                    continue;
                }

                DB::table('arbmedvv_occasions')
                    ->where('team_id', $teamId)
                    ->whereNull('examination_id')               // vorhandene Links nicht überschreiben
                    ->where(function ($w) use ($conditions) {
                        foreach ($conditions as [$col, $op, $val]) {
                            $w->orWhere($col, $op, $val);
                        }
                    })
                    ->update(['examination_id' => $examId, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Diese Migration ist der einzige Setter von examination_id → alle wieder lösen.
        DB::table('arbmedvv_occasions')->whereNotNull('examination_id')->update(['examination_id' => null]);
    }
};
