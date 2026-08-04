<?php

namespace Platform\Arbmedvv\Services;

use Platform\Arbmedvv\Models\Anlass;

/**
 * Dünner Service-Layer für die Anlass-CRUD-Logik.
 * Wird von Livewire-Komponenten und LLM-Tools gemeinsam genutzt.
 */
class AnlassService
{
    public function createAnlass(array $data): Anlass
    {
        if (!isset($data['position'])) {
            $data['position'] = $this->nextPosition((int) $data['team_id'], (string) $data['teil']);
        }

        return Anlass::create($data);
    }

    public function updateAnlass(Anlass $anlass, array $data): Anlass
    {
        $anlass->update($data);

        return $anlass->fresh();
    }

    public function deleteAnlass(Anlass $anlass): void
    {
        $anlass->delete();
    }

    /**
     * Nächste Sortierposition innerhalb eines Teils (pro Team).
     */
    public function nextPosition(int $teamId, string $teil): int
    {
        return (int) Anlass::forTeam($teamId)->byTeil($teil)->max('position') + 1;
    }
}
