<?php

namespace Platform\Arbmedvv\Services;

use Platform\Arbmedvv\Models\Occasion;

/**
 * Thin service layer for the occasion CRUD logic.
 * Shared by Livewire components and LLM tools.
 */
class OccasionService
{
    public function create(array $data): Occasion
    {
        if (!isset($data['position'])) {
            $data['position'] = $this->nextPosition((int) $data['team_id'], (string) $data['section']);
        }

        return Occasion::create($data);
    }

    public function update(Occasion $occasion, array $data): Occasion
    {
        $occasion->update($data);

        return $occasion->fresh();
    }

    public function delete(Occasion $occasion): void
    {
        $occasion->delete();
    }

    /**
     * Next sort position within a section (per team).
     */
    public function nextPosition(int $teamId, string $section): int
    {
        return (int) Occasion::forTeam($teamId)->bySection($section)->max('position') + 1;
    }
}
