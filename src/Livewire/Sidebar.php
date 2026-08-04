<?php

namespace Platform\Arbmedvv\Livewire;

use Livewire\Component;
use Platform\Arbmedvv\Models\Anlass;

/**
 * Modul-Nav-Sidebar für ArbMedVV.
 *
 * Wird von der Platform-Shell automatisch als `@livewire('arbmedvv.sidebar')`
 * eingebunden (siehe core layouts/app.blade.php). Zeigt die Modul-Navigation
 * plus die 4 Teile mit Anzahl als Schnellfilter.
 */
class Sidebar extends Component
{
    public function render()
    {
        $user = auth()->user();
        $team = $user?->currentTeam;

        $teilCounts = [];
        if ($team) {
            $teilCounts = Anlass::forTeam($team->id)
                ->active()
                ->selectRaw('teil, COUNT(*) as c')
                ->groupBy('teil')
                ->pluck('c', 'teil')
                ->all();
        }

        return view('arbmedvv::livewire.sidebar', [
            'teileKurz'  => config('arbmedvv.teile_kurz'),
            'teilCounts' => $teilCounts,
        ]);
    }
}
