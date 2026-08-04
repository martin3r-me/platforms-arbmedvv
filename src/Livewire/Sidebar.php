<?php

namespace Platform\Arbmedvv\Livewire;

use Livewire\Component;
use Platform\Arbmedvv\Models\Occasion;

/**
 * Module nav sidebar for ArbMedVV.
 *
 * Rendered automatically by the platform shell as `@livewire('arbmedvv.sidebar')`
 * (see core layouts/app.blade.php). Shows the module navigation plus the 4 sections
 * with counts as quick filters.
 */
class Sidebar extends Component
{
    public function render()
    {
        $user = auth()->user();
        $team = $user?->currentTeam;

        $sectionCounts = [];
        if ($team) {
            $sectionCounts = Occasion::forTeam($team->id)
                ->active()
                ->selectRaw('section, COUNT(*) as c')
                ->groupBy('section')
                ->pluck('c', 'section')
                ->all();
        }

        return view('arbmedvv::livewire.sidebar', [
            'sectionsShort' => config('arbmedvv.sections_short'),
            'sectionCounts' => $sectionCounts,
        ]);
    }
}
