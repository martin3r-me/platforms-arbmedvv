<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Arbmedvv\Models\Anlass;
use Platform\Arbmedvv\Services\AnlassService;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class DeleteAnlassTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.anlaesse.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /arbmedvv/anlaesse - Loescht einen Vorsorge-Anlass (soft-delete). ERFORDERLICH: anlass_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'anlass_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Anlasses (ERFORDERLICH).',
                ],
            ],
            'required' => ['anlass_id'],
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

            $anlassId = (int) ($arguments['anlass_id'] ?? 0);
            if ($anlassId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'anlass_id ist erforderlich.');
            }

            $anlass = Anlass::query()->where('team_id', $teamId)->find($anlassId);
            if (!$anlass) {
                return ToolResult::error('NOT_FOUND', 'Anlass nicht gefunden (oder kein Zugriff).');
            }

            $titel = $anlass->titel;
            (new AnlassService())->deleteAnlass($anlass);

            return ToolResult::success([
                'id' => $anlassId,
                'message' => "Anlass '{$titel}' geloescht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen des Anlasses: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['arbmedvv', 'anlaesse', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
