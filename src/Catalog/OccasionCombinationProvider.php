<?php

namespace Platform\Arbmedvv\Catalog;

use Platform\Core\Contracts\CatalogCombinationProvider;
use Platform\Arbmedvv\Models\Occasion;

/**
 * Liefert der Core-Registry die Vermengungsgruppe eines ArbMedVV-Anlasses
 * (morphMap-Alias 'arbmedvv_occasion'). ArbMedVV = Vorsorge.
 */
class OccasionCombinationProvider implements CatalogCombinationProvider
{
    public function supportedTypes(): array
    {
        return ['arbmedvv_occasion'];
    }

    public function combinationGroup(string $catalogType, int $catalogId): ?string
    {
        if ($catalogType !== 'arbmedvv_occasion' || $catalogId <= 0) {
            return null;
        }
        $val = Occasion::query()->whereKey($catalogId)->value('combination_group');

        return ($val !== null && $val !== '') ? (string) $val : null;
    }
}
