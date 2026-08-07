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

class UpdateOccasionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.occasions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /arbmedvv/occasions - Updates a preventive-care occasion. REQUIRED: occasion_id. Optional: section, care_type, title, trigger, threshold, legal_basis, description, status.';
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
                'section' => [
                    'type' => 'string',
                    'enum' => ['hazardous_substances', 'biological_agents', 'physical_agents', 'other'],
                    'description' => 'Optional: new section.',
                ],
                'care_type' => [
                    'type' => 'string',
                    'enum' => ['mandatory', 'offered', 'request', 'follow_up'],
                    'description' => 'Optional: new care type.',
                ],
                'title' => ['type' => 'string', 'description' => 'Optional: new title.'],
                'trigger' => ['type' => 'string', 'description' => 'Optional: new trigger wording.'],
                'threshold' => ['type' => 'string', 'description' => 'Optional: new threshold (empty string = clear).'],
                'legal_basis' => ['type' => 'string', 'description' => 'Optional: new legal basis (empty string = clear).'],
                'description' => ['type' => 'string', 'description' => 'Optional: new description (empty string = clear).'],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'archived'],
                    'description' => 'Optional: new status.',
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

            $payload = [];

            if (array_key_exists('section', $arguments) && $arguments['section'] !== null) {
                if (!in_array($arguments['section'], ['hazardous_substances', 'biological_agents', 'physical_agents', 'other'], true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'section is invalid.');
                }
                $payload['section'] = $arguments['section'];
            }
            if (array_key_exists('care_type', $arguments) && $arguments['care_type'] !== null) {
                if (!in_array($arguments['care_type'], ['mandatory', 'offered', 'request', 'follow_up'], true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'care_type is invalid.');
                }
                $payload['care_type'] = $arguments['care_type'];
            }
            if (array_key_exists('title', $arguments) && $arguments['title'] !== null) {
                $title = trim((string) $arguments['title']);
                if ($title === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'title must not be empty.');
                }
                $payload['title'] = $title;
            }
            if (array_key_exists('trigger', $arguments) && $arguments['trigger'] !== null) {
                $trigger = trim((string) $arguments['trigger']);
                if ($trigger === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'trigger must not be empty.');
                }
                $payload['trigger'] = $trigger;
            }
            foreach (['threshold', 'legal_basis', 'description'] as $optional) {
                if (array_key_exists($optional, $arguments)) {
                    $val = $arguments[$optional];
                    $payload[$optional] = ($val === null || trim((string) $val) === '') ? null : trim((string) $val);
                }
            }
            if (array_key_exists('status', $arguments) && $arguments['status'] !== null) {
                if (!in_array($arguments['status'], ['active', 'archived'], true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'status must be active or archived.');
                }
                $payload['status'] = $arguments['status'];
            }

            if (empty($payload)) {
                return ToolResult::error('NO_CHANGE', 'No changes provided.');
            }

            $occasion = (new OccasionService())->update($occasion, $payload);

            return ToolResult::success([
                'id' => $occasion->id,
                'uuid' => $occasion->uuid,
                'section' => $occasion->section,
                'care_type' => $occasion->care_type,
                'title' => $occasion->title,
                'status' => $occasion->status,
                'team_id' => $occasion->team_id,
                'message' => "Occasion '{$occasion->title}' updated successfully.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating occasion: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['arbmedvv', 'occasions', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
