<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Arbmedvv\Models\Occasion;
use Platform\Arbmedvv\Services\OccasionService;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class DeleteOccasionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.occasions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /arbmedvv/occasions - Deletes a preventive-care occasion (soft-delete). REQUIRED: occasion_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
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
        ]);
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

            $title = $occasion->title;
            (new OccasionService())->delete($occasion);

            return ToolResult::success([
                'id' => $occasionId,
                'message' => "Occasion '{$title}' deleted.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting occasion: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['arbmedvv', 'occasions', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
