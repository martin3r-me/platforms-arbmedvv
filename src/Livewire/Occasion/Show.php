<?php

namespace Platform\Arbmedvv\Livewire\Occasion;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Arbmedvv\Models\Occasion;
use Platform\Arbmedvv\Services\OccasionService;

/**
 * Detail + inline edit of an occasion.
 */
class Show extends Component
{
    public Occasion $occasion;

    public bool $editing = false;
    public array $form = [];

    public function mount(Occasion $occasion): void
    {
        $team = Auth::user()?->currentTeam;

        if (!$team || $occasion->team_id !== $team->id) {
            abort(403);
        }

        $this->occasion = $occasion;
    }

    protected function rules(): array
    {
        return [
            'form.section'     => 'required|in:hazardous_substances,biological_agents,physical_agents,other',
            'form.care_type'   => 'required|in:mandatory,offered,follow_up',
            'form.combination_group' => 'nullable|string|max:64',
            'form.title'       => 'required|string|max:255',
            'form.trigger'     => 'required|string',
            'form.threshold'   => 'nullable|string|max:255',
            'form.legal_basis' => 'nullable|string|max:255',
            'form.description' => 'nullable|string',
            'form.status'      => 'required|in:active,archived',
        ];
    }

    public function edit(): void
    {
        $this->form = [
            'section'     => $this->occasion->section,
            'care_type'   => $this->occasion->care_type,
            'combination_group' => $this->occasion->combination_group ?? 'vorsorge',
            'title'       => $this->occasion->title,
            'trigger'     => $this->occasion->trigger,
            'threshold'   => $this->occasion->threshold ?? '',
            'legal_basis' => $this->occasion->legal_basis ?? '',
            'description' => $this->occasion->description ?? '',
            'status'      => $this->occasion->status,
        ];
        $this->resetErrorBag();
        $this->editing = true;
    }

    public function save(OccasionService $service): void
    {
        $this->validate();

        $service->update($this->occasion, [
            'section'     => $this->form['section'],
            'care_type'   => $this->form['care_type'],
            'combination_group' => $this->form['combination_group'] !== '' ? trim($this->form['combination_group']) : null,
            'title'       => trim($this->form['title']),
            'trigger'     => trim($this->form['trigger']),
            'threshold'   => $this->form['threshold'] !== '' ? trim($this->form['threshold']) : null,
            'legal_basis' => $this->form['legal_basis'] !== '' ? trim($this->form['legal_basis']) : null,
            'description' => $this->form['description'] !== '' ? trim($this->form['description']) : null,
            'status'      => $this->form['status'],
        ]);

        $this->occasion->refresh();
        $this->editing = false;
    }

    public function delete(OccasionService $service)
    {
        $service->delete($this->occasion);

        return $this->redirect(route('arbmedvv.occasions.index'), navigate: true);
    }

    public function render()
    {
        $this->occasion->loadMissing('createdByUser');

        return view('arbmedvv::livewire.occasion.show', [
            'sections'  => config('arbmedvv.sections'),
            'careTypes' => config('arbmedvv.care_types'),
            'combinationGroups' => config('arbmedvv.combination_groups', []),
        ])->layout('platform::layouts.app');
    }
}
