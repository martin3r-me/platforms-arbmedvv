<?php

namespace Platform\Arbmedvv\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Arbmedvv\Models\Occasion;

/**
 * ArbMedVV Dashboard – catalog overview.
 */
class Dashboard extends Component
{
    public function render()
    {
        $team = Auth::user()?->currentTeam;

        $occasions = $team
            ? Occasion::forTeam($team->id)->active()->orderBy('section')->orderBy('position')->get()
            : collect();

        $stats = [
            'total'     => $occasions->count(),
            'mandatory' => $occasions->where('care_type', 'mandatory')->count(),
            'offered'   => $occasions->where('care_type', 'offered')->count(),
            'follow_up' => $occasions->where('care_type', 'follow_up')->count(),
        ];

        // Grouped by section, in the statutory order
        $sections = config('arbmedvv.sections');
        $grouped = collect($sections)->map(function ($label, $key) use ($occasions) {
            return [
                'key'       => $key,
                'label'     => $label,
                'occasions' => $occasions->where('section', $key)->take(5)->values(),
                'count'     => $occasions->where('section', $key)->count(),
            ];
        })->values();

        // Recently changed occasions (right activity sidebar)
        $recent = $occasions->sortByDesc('updated_at')->take(5)->values();

        return view('arbmedvv::livewire.dashboard', [
            'stats'       => $stats,
            'grouped'     => $grouped,
            'recent'      => $recent,
            'currentDate' => now()->format('d.m.Y'),
        ])->layout('platform::layouts.app');
    }
}
