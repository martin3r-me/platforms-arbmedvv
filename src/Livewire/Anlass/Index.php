<?php

namespace Platform\Arbmedvv\Livewire\Anlass;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Arbmedvv\Models\Anlass;
use Platform\Arbmedvv\Services\AnlassService;

/**
 * Anlass-Katalog: Liste (nach Teil gruppiert) mit Suche, Filter und Anlege-Modal.
 */
class Index extends Component
{
    public string $search = '';
    public string $filterTeil = '';
    public string $filterVorsorgeart = '';

    // Anlege-Modal
    public bool $showCreate = false;
    public array $form = [
        'teil'            => 'gefahrstoffe',
        'vorsorgeart'     => 'pflicht',
        'titel'           => '',
        'ausloeser'       => '',
        'grenzwert'       => '',
        'rechtsgrundlage' => '',
        'beschreibung'    => '',
    ];

    protected function rules(): array
    {
        return [
            'form.teil'            => 'required|in:gefahrstoffe,biostoffe,physikalisch,sonstige',
            'form.vorsorgeart'     => 'required|in:pflicht,angebot,nachgehend',
            'form.titel'           => 'required|string|max:255',
            'form.ausloeser'       => 'required|string',
            'form.grenzwert'       => 'nullable|string|max:255',
            'form.rechtsgrundlage' => 'nullable|string|max:255',
            'form.beschreibung'    => 'nullable|string',
        ];
    }

    public function openCreate(): void
    {
        $this->reset('form');
        $this->form['teil'] = $this->filterTeil ?: 'gefahrstoffe';
        $this->form['vorsorgeart'] = $this->filterVorsorgeart ?: 'pflicht';
        $this->resetErrorBag();
        $this->showCreate = true;
    }

    public function save(AnlassService $service): void
    {
        $this->validate();

        $team = Auth::user()->currentTeam;

        $service->createAnlass([
            'team_id'             => $team->id,
            'created_by_user_id'  => Auth::id(),
            'teil'                => $this->form['teil'],
            'vorsorgeart'         => $this->form['vorsorgeart'],
            'titel'               => trim($this->form['titel']),
            'ausloeser'           => trim($this->form['ausloeser']),
            'grenzwert'           => $this->form['grenzwert'] !== '' ? trim($this->form['grenzwert']) : null,
            'rechtsgrundlage'     => $this->form['rechtsgrundlage'] !== '' ? trim($this->form['rechtsgrundlage']) : null,
            'beschreibung'        => $this->form['beschreibung'] !== '' ? trim($this->form['beschreibung']) : null,
        ]);

        $this->showCreate = false;
        $this->reset('form');
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $team = Auth::user()?->currentTeam;

        $query = $team ? Anlass::forTeam($team->id) : Anlass::whereRaw('1 = 0');

        if ($this->filterTeil !== '') {
            $query->byTeil($this->filterTeil);
        }
        if ($this->filterVorsorgeart !== '') {
            $query->byVorsorgeart($this->filterVorsorgeart);
        }
        if ($this->search !== '') {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('titel', 'like', "%{$s}%")
                  ->orWhere('ausloeser', 'like', "%{$s}%")
                  ->orWhere('grenzwert', 'like', "%{$s}%")
                  ->orWhere('rechtsgrundlage', 'like', "%{$s}%");
            });
        }

        $anlaesse = $query->orderBy('teil')->orderBy('position')->get();

        $teile = config('arbmedvv.teile');
        $grouped = collect($teile)
            ->map(fn ($label, $key) => [
                'key'      => $key,
                'label'    => $label,
                'anlaesse' => $anlaesse->where('teil', $key)->values(),
            ])
            ->filter(fn ($g) => $g['anlaesse']->isNotEmpty())
            ->values();

        return view('arbmedvv::livewire.anlass.index', [
            'grouped'       => $grouped,
            'total'         => $anlaesse->count(),
            'teile'         => $teile,
            'vorsorgearten' => config('arbmedvv.vorsorgearten'),
        ])->layout('platform::layouts.app');
    }
}
