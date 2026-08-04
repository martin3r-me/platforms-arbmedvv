<?php

namespace Platform\Arbmedvv\Livewire\Occasion;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Arbmedvv\Models\Occasion;
use Platform\Arbmedvv\Services\OccasionService;

/**
 * Occasion catalog: list (grouped by section) with search, filters and a create modal.
 */
class Index extends Component
{
    public string $search = '';
    public string $filterSection = '';
    public string $filterCareType = '';

    // Create modal
    public bool $showCreate = false;
    public array $form = [
        'section'     => 'hazardous_substances',
        'care_type'   => 'mandatory',
        'title'       => '',
        'trigger'     => '',
        'threshold'   => '',
        'legal_basis' => '',
        'description' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.section'     => 'required|in:hazardous_substances,biological_agents,physical_agents,other',
            'form.care_type'   => 'required|in:mandatory,offered,follow_up',
            'form.title'       => 'required|string|max:255',
            'form.trigger'     => 'required|string',
            'form.threshold'   => 'nullable|string|max:255',
            'form.legal_basis' => 'nullable|string|max:255',
            'form.description' => 'nullable|string',
        ];
    }

    public function openCreate(): void
    {
        $this->reset('form');
        $this->form['section'] = $this->filterSection ?: 'hazardous_substances';
        $this->form['care_type'] = $this->filterCareType ?: 'mandatory';
        $this->resetErrorBag();
        $this->showCreate = true;
    }

    public function save(OccasionService $service): void
    {
        $this->validate();

        $team = Auth::user()->currentTeam;

        $service->create([
            'team_id'            => $team->id,
            'created_by_user_id' => Auth::id(),
            'section'            => $this->form['section'],
            'care_type'          => $this->form['care_type'],
            'title'              => trim($this->form['title']),
            'trigger'            => trim($this->form['trigger']),
            'threshold'          => $this->form['threshold'] !== '' ? trim($this->form['threshold']) : null,
            'legal_basis'        => $this->form['legal_basis'] !== '' ? trim($this->form['legal_basis']) : null,
            'description'        => $this->form['description'] !== '' ? trim($this->form['description']) : null,
        ]);

        $this->showCreate = false;
        $this->reset('form');
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $team = Auth::user()?->currentTeam;

        $query = $team ? Occasion::forTeam($team->id) : Occasion::whereRaw('1 = 0');

        if ($this->filterSection !== '') {
            $query->bySection($this->filterSection);
        }
        if ($this->filterCareType !== '') {
            $query->byCareType($this->filterCareType);
        }
        if ($this->search !== '') {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('trigger', 'like', "%{$s}%")
                  ->orWhere('threshold', 'like', "%{$s}%")
                  ->orWhere('legal_basis', 'like', "%{$s}%");
            });
        }

        $occasions = $query->orderBy('section')->orderBy('position')->get();

        $sections = config('arbmedvv.sections');
        $grouped = collect($sections)
            ->map(fn ($label, $key) => [
                'key'       => $key,
                'label'     => $label,
                'occasions' => $occasions->where('section', $key)->values(),
            ])
            ->filter(fn ($g) => $g['occasions']->isNotEmpty())
            ->values();

        // Recently changed occasions (right activity sidebar), regardless of filter
        $recent = $team
            ? Occasion::forTeam($team->id)->orderByDesc('updated_at')->take(5)->get()
            : collect();

        return view('arbmedvv::livewire.occasion.index', [
            'grouped'   => $grouped,
            'total'     => $occasions->count(),
            'recent'    => $recent,
            'sections'  => $sections,
            'careTypes' => config('arbmedvv.care_types'),
        ])->layout('platform::layouts.app');
    }
}
