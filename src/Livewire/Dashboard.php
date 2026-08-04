<?php

namespace Platform\Arbmedvv\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Arbmedvv\Models\Anlass;

/**
 * ArbMedVV Dashboard – Katalog-Übersicht.
 */
class Dashboard extends Component
{
    public function render()
    {
        $team = Auth::user()?->currentTeam;

        $anlaesse = $team
            ? Anlass::forTeam($team->id)->active()->orderBy('teil')->orderBy('position')->get()
            : collect();

        $stats = [
            'total'      => $anlaesse->count(),
            'pflicht'    => $anlaesse->where('vorsorgeart', 'pflicht')->count(),
            'angebot'    => $anlaesse->where('vorsorgeart', 'angebot')->count(),
            'nachgehend' => $anlaesse->where('vorsorgeart', 'nachgehend')->count(),
        ];

        // Gruppiert nach Teil, in der gesetzlichen Reihenfolge
        $teile = config('arbmedvv.teile');
        $grouped = collect($teile)->map(function ($label, $key) use ($anlaesse) {
            return [
                'key'      => $key,
                'label'    => $label,
                'anlaesse' => $anlaesse->where('teil', $key)->take(5)->values(),
                'count'    => $anlaesse->where('teil', $key)->count(),
            ];
        })->values();

        return view('arbmedvv::livewire.dashboard', [
            'stats'       => $stats,
            'grouped'     => $grouped,
            'currentDate' => now()->format('d.m.Y'),
        ])->layout('platform::layouts.app');
    }
}
