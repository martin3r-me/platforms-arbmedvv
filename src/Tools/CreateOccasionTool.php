<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Arbmedvv\Services\OccasionService;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class CreateOccasionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.occasions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /arbmedvv/occasions - Creates a preventive-care occasion. REQUIRED: section (hazardous_substances|biological_agents|physical_agents|other), care_type (mandatory|offered|follow_up), title, trigger. Optional: threshold, legal_basis, description, status (default: active).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: team id. Default: current team from context.',
                ],
                'section' => [
                    'type' => 'string',
                    'enum' => ['hazardous_substances', 'biological_agents', 'physical_agents', 'other'],
                    'description' => 'Annex part. Optional — leave empty for annex-independent Wunschvorsorge (§5a).',
                ],
                'care_type' => [
                    'type' => 'string',
                    'enum' => ['mandatory', 'offered', 'request', 'follow_up'],
                    'description' => 'Type of preventive care (REQUIRED). request = Wunschvorsorge (§5a).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Short name, e.g. "Feuchtarbeit" (REQUIRED).',
                ],
                'trigger' => [
                    'type' => 'string',
                    'description' => 'Verbatim wording of the triggering hazard/exposure statement (REQUIRED).',
                ],
                'threshold' => [
                    'type' => 'string',
                    'description' => 'Optional: threshold, e.g. ">= 4 h/day", "85 dB(A)".',
                ],
                'legal_basis' => [
                    'type' => 'string',
                    'description' => 'Optional: reference, e.g. "Annex Part 1 (2)".',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: notes/hints.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'archived'],
                    'description' => 'Optional: status. Default: active.',
                ],
                'valid_from' => [
                    'type' => 'string',
                    'description' => 'Optional: valid-from date (YYYY-MM-DD) for versioning/novellierungen.',
                ],
                'valid_until' => [
                    'type' => 'string',
                    'description' => 'Optional: valid-until date (YYYY-MM-DD). Empty = currently valid.',
                ],
                'regulation_label' => [
                    'type' => 'string',
                    'description' => 'Optional: regulation version label, e.g. "ArbMedVV Stand 2019".',
                ],
            ],
            'required' => ['care_type', 'title', 'trigger'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user in context.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            // section optional (leer = anhangsunabhängig, z.B. Wunschvorsorge §5a).
            $section = trim((string) ($arguments['section'] ?? ''));
            if ($section === '') {
                $section = null;
            } elseif (!in_array($section, ['hazardous_substances', 'biological_agents', 'physical_agents', 'other'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'section is invalid.');
            }
            $careType = (string) ($arguments['care_type'] ?? '');
            if (!in_array($careType, ['mandatory', 'offered', 'request', 'follow_up'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'care_type is invalid.');
            }
            $title = trim((string) ($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title is required.');
            }
            $trigger = trim((string) ($arguments['trigger'] ?? ''));
            if ($trigger === '') {
                return ToolResult::error('VALIDATION_ERROR', 'trigger is required.');
            }
            $status = $arguments['status'] ?? 'active';
            if (!in_array($status, ['active', 'archived'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'status must be active or archived.');
            }

            $occasion = (new OccasionService())->create([
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
                'section' => $section,
                'care_type' => $careType,
                'title' => $title,
                'trigger' => $trigger,
                'threshold' => isset($arguments['threshold']) && $arguments['threshold'] !== '' ? trim((string) $arguments['threshold']) : null,
                'legal_basis' => isset($arguments['legal_basis']) && $arguments['legal_basis'] !== '' ? trim((string) $arguments['legal_basis']) : null,
                'description' => isset($arguments['description']) && $arguments['description'] !== '' ? trim((string) $arguments['description']) : null,
                'status' => $status,
                'valid_from' => isset($arguments['valid_from']) && $arguments['valid_from'] !== '' ? (string) $arguments['valid_from'] : null,
                'valid_until' => isset($arguments['valid_until']) && $arguments['valid_until'] !== '' ? (string) $arguments['valid_until'] : null,
                'regulation_label' => isset($arguments['regulation_label']) && $arguments['regulation_label'] !== '' ? trim((string) $arguments['regulation_label']) : null,
            ]);

            return ToolResult::success([
                'id' => $occasion->id,
                'uuid' => $occasion->uuid,
                'section' => $occasion->section,
                'care_type' => $occasion->care_type,
                'title' => $occasion->title,
                'status' => $occasion->status,
                'team_id' => $occasion->team_id,
                'message' => "Occasion '{$occasion->title}' created successfully.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating occasion: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['arbmedvv', 'occasions', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
