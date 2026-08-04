<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ArbmedvvOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'arbmedvv.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /arbmedvv/overview - Overview of the ArbMedVV module (concept, data model, taxonomy, tools). The module is a team-wide catalog of the occupational-health preventive-care occasions from the annex of the ArbMedVV.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            return ToolResult::success([
                'module' => 'arbmedvv',
                'scope' => [
                    'team_scoped' => true,
                    'team_id_source' => 'ToolContext.team or team_id parameter',
                ],
                'concepts' => [
                    'occasion' => [
                        'model' => 'Platform\\Arbmedvv\\Models\\Occasion',
                        'table' => 'arbmedvv_occasions',
                        'morph_alias' => 'arbmedvv_occasion',
                        'key_fields' => ['id', 'uuid', 'section', 'care_type', 'title', 'trigger', 'threshold', 'legal_basis', 'description', 'status', 'position', 'team_id'],
                        'note' => 'One record = one preventive-care occasion from the annex of the ArbMedVV.',
                    ],
                ],
                'taxonomy' => [
                    'section' => config('arbmedvv.sections'),
                    'care_type' => config('arbmedvv.care_types'),
                    'status' => ['active', 'archived'],
                ],
                'fields' => [
                    'title' => 'Short name, e.g. "Feuchtarbeit".',
                    'trigger' => 'Verbatim wording of the triggering hazard/exposure statement.',
                    'threshold' => 'Optional threshold, e.g. ">= 4 h/day", "85 dB(A)".',
                    'legal_basis' => 'Reference, e.g. "Annex Part 1 (2)".',
                ],
                'related_tools' => [
                    'list' => 'arbmedvv.occasions.GET',
                    'get' => 'arbmedvv.occasion.GET',
                    'create' => 'arbmedvv.occasions.POST',
                    'update' => 'arbmedvv.occasions.PUT',
                    'delete' => 'arbmedvv.occasions.DELETE',
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading ArbMedVV overview: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'arbmedvv'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
