<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Arbmedvv\Models\Anlass;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class GetAnlassTool implements ToolContract, ToolMetadataContract
{
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.anlass.GET';
    }

    public function getDescription(): string
    {
        return 'GET /arbmedvv/anlass - Zeigt einen einzelnen Vorsorge-Anlass. ERFORDERLICH: anlass_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
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

            $anlassId = (int) ($arguments['anlass_id'] ?? 0);
            if ($anlassId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'anlass_id ist erforderlich.');
            }

            $anlass = Anlass::query()->where('team_id', $teamId)->find($anlassId);
            if (!$anlass) {
                return ToolResult::error('NOT_FOUND', 'Anlass nicht gefunden (oder kein Zugriff).');
            }

            return ToolResult::success([
                'id' => $anlass->id,
                'uuid' => $anlass->uuid,
                'teil' => $anlass->teil,
                'teil_label' => $anlass->teilLabel(),
                'vorsorgeart' => $anlass->vorsorgeart,
                'vorsorgeart_label' => $anlass->vorsorgeartLabel(),
                'titel' => $anlass->titel,
                'ausloeser' => $anlass->ausloeser,
                'grenzwert' => $anlass->grenzwert,
                'rechtsgrundlage' => $anlass->rechtsgrundlage,
                'beschreibung' => $anlass->beschreibung,
                'status' => $anlass->status,
                'position' => $anlass->position,
                'team_id' => $anlass->team_id,
                'created_at' => $anlass->created_at?->toISOString(),
                'updated_at' => $anlass->updated_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Anlasses: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['arbmedvv', 'anlass', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
