<?php

namespace Platform\Arbmedvv\Livewire\Anlass;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Arbmedvv\Models\Anlass;
use Platform\Arbmedvv\Services\AnlassService;

/**
 * Detail + Inline-Edit eines Anlasses.
 */
class Show extends Component
{
    public Anlass $anlass;

    public bool $editing = false;
    public array $form = [];

    public function mount(Anlass $anlass): void
    {
        $team = Auth::user()?->currentTeam;

        if (!$team || $anlass->team_id !== $team->id) {
            abort(403);
        }

        $this->anlass = $anlass;
    }

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
            'form.status'          => 'required|in:aktiv,archiviert',
        ];
    }

    public function edit(): void
    {
        $this->form = [
            'teil'            => $this->anlass->teil,
            'vorsorgeart'     => $this->anlass->vorsorgeart,
            'titel'           => $this->anlass->titel,
            'ausloeser'       => $this->anlass->ausloeser,
            'grenzwert'       => $this->anlass->grenzwert ?? '',
            'rechtsgrundlage' => $this->anlass->rechtsgrundlage ?? '',
            'beschreibung'    => $this->anlass->beschreibung ?? '',
            'status'          => $this->anlass->status,
        ];
        $this->resetErrorBag();
        $this->editing = true;
    }

    public function save(AnlassService $service): void
    {
        $this->validate();

        $service->updateAnlass($this->anlass, [
            'teil'            => $this->form['teil'],
            'vorsorgeart'     => $this->form['vorsorgeart'],
            'titel'           => trim($this->form['titel']),
            'ausloeser'       => trim($this->form['ausloeser']),
            'grenzwert'       => $this->form['grenzwert'] !== '' ? trim($this->form['grenzwert']) : null,
            'rechtsgrundlage' => $this->form['rechtsgrundlage'] !== '' ? trim($this->form['rechtsgrundlage']) : null,
            'beschreibung'    => $this->form['beschreibung'] !== '' ? trim($this->form['beschreibung']) : null,
            'status'          => $this->form['status'],
        ]);

        $this->anlass->refresh();
        $this->editing = false;
    }

    public function delete(AnlassService $service)
    {
        $service->deleteAnlass($this->anlass);

        return $this->redirect(route('arbmedvv.anlaesse.index'), navigate: true);
    }

    public function render()
    {
        $this->anlass->loadMissing('createdByUser');

        return view('arbmedvv::livewire.anlass.show', [
            'teile'         => config('arbmedvv.teile'),
            'vorsorgearten' => config('arbmedvv.vorsorgearten'),
        ])->layout('platform::layouts.app');
    }
}
