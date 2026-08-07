<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Arbmedvv\Models\Occasion;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class GetOccasionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.occasion.GET';
    }

    public function getDescription(): string
    {
        return 'GET /arbmedvv/occasion - Shows a single preventive-care occasion. REQUIRED: occasion_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: team id. Default: current team from context.',
                ],
                'occasion_id' => [
                    'type' => 'integer',
                    'description' => 'Id of the occasion (REQUIRED).',
                ],
            ],
            'required' => ['occasion_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $occasionId = (int) ($arguments['occasion_id'] ?? 0);
            if ($occasionId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'occasion_id is required.');
            }

            $occasion = Occasion::query()->where('team_id', $teamId)->find($occasionId);
            if (!$occasion) {
                return ToolResult::error('NOT_FOUND', 'Occasion not found (or no access).');
            }

            return ToolResult::success([
                'id' => $occasion->id,
                'uuid' => $occasion->uuid,
                'section' => $occasion->section,
                'section_label' => $occasion->sectionLabel(),
                'care_type' => $occasion->care_type,
                'care_type_label' => $occasion->careTypeLabel(),
                'title' => $occasion->title,
                'trigger' => $occasion->trigger,
                'threshold' => $occasion->threshold,
                'legal_basis' => $occasion->legal_basis,
                'description' => $occasion->description,
                'status' => $occasion->status,
                'version' => $occasion->version,
                'valid_from' => $occasion->valid_from?->toDateString(),
                'valid_until' => $occasion->valid_until?->toDateString(),
                'currently_valid' => $occasion->isCurrentlyValid(),
                'regulation_label' => $occasion->regulation_label,
                'position' => $occasion->position,
                'team_id' => $occasion->team_id,
                'created_at' => $occasion->created_at?->toISOString(),
                'updated_at' => $occasion->updated_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading occasion: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['arbmedvv', 'occasion', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
