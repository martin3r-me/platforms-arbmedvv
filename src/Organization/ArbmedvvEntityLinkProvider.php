<?php

namespace Platform\Arbmedvv\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Arbmedvv\Models\Occasion;

/**
 * Makes ArbMedVV occasions render richly when linked to organization entities
 * via dimension links (alias "arbmedvv_occasion"). Follows the planner pattern
 * (see Platform\Planner\Organization\PlannerEntityLinkProvider).
 */
class ArbmedvvEntityLinkProvider implements EntityLinkProvider
{
    public function morphAliases(): array
    {
        return ['arbmedvv_occasion'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'arbmedvv_occasion' => [
                'label'    => 'Vorsorge-Anlässe',
                'singular' => 'Anlass',
                'icon'     => 'clipboard-document-check',
                'route'    => 'arbmedvv.occasions.show',
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        // Catalog records, no children to eager-load.
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        if ($morphAlias !== 'arbmedvv_occasion' || !$model instanceof Occasion) {
            return [];
        }

        return [
            'title'       => $model->title,
            'section'     => $model->sectionShortLabel(),
            'care_type'   => $model->careTypeLabel(),
            'threshold'   => $model->threshold,
            'legal_basis' => $model->legal_basis,
            'status'      => $model->status,
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'arbmedvv_occasion' => [
                ['field' => 'care_type', 'format' => 'badge'],
                ['field' => 'section', 'format' => 'text'],
                ['field' => 'threshold', 'format' => 'text'],
                ['field' => 'legal_basis', 'format' => 'text'],
                ['field' => 'status', 'format' => 'badge'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        if ($morphAlias !== 'arbmedvv_occasion') {
            return [];
        }

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $result[$entityId] = [
                'arbmedvv_occasions_count' => count($ids),
            ];
        }

        return $result;
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }
}
