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

class UpdateAnlassTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.anlaesse.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /arbmedvv/anlaesse - Aktualisiert einen Vorsorge-Anlass. ERFORDERLICH: anlass_id. Optional: teil, vorsorgeart, titel, ausloeser, grenzwert, rechtsgrundlage, beschreibung, status.';
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
                'teil' => [
                    'type' => 'string',
                    'enum' => ['gefahrstoffe', 'biostoffe', 'physikalisch', 'sonstige'],
                    'description' => 'Optional: Neuer Teil.',
                ],
                'vorsorgeart' => [
                    'type' => 'string',
                    'enum' => ['pflicht', 'angebot', 'nachgehend'],
                    'description' => 'Optional: Neue Vorsorgeart.',
                ],
                'titel' => ['type' => 'string', 'description' => 'Optional: Neuer Titel.'],
                'ausloeser' => ['type' => 'string', 'description' => 'Optional: Neuer Auslöser-Wortlaut.'],
                'grenzwert' => ['type' => 'string', 'description' => 'Optional: Neuer Grenzwert (leerer String = löschen).'],
                'rechtsgrundlage' => ['type' => 'string', 'description' => 'Optional: Neue Rechtsgrundlage (leerer String = löschen).'],
                'beschreibung' => ['type' => 'string', 'description' => 'Optional: Neue Beschreibung (leerer String = löschen).'],
                'status' => [
                    'type' => 'string',
                    'enum' => ['aktiv', 'archiviert'],
                    'description' => 'Optional: Neuer Status.',
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

            $payload = [];

            if (array_key_exists('teil', $arguments) && $arguments['teil'] !== null) {
                if (!in_array($arguments['teil'], ['gefahrstoffe', 'biostoffe', 'physikalisch', 'sonstige'], true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'teil ist ungueltig.');
                }
                $payload['teil'] = $arguments['teil'];
            }
            if (array_key_exists('vorsorgeart', $arguments) && $arguments['vorsorgeart'] !== null) {
                if (!in_array($arguments['vorsorgeart'], ['pflicht', 'angebot', 'nachgehend'], true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'vorsorgeart ist ungueltig.');
                }
                $payload['vorsorgeart'] = $arguments['vorsorgeart'];
            }
            if (array_key_exists('titel', $arguments) && $arguments['titel'] !== null) {
                $titel = trim((string) $arguments['titel']);
                if ($titel === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'titel darf nicht leer sein.');
                }
                $payload['titel'] = $titel;
            }
            if (array_key_exists('ausloeser', $arguments) && $arguments['ausloeser'] !== null) {
                $ausloeser = trim((string) $arguments['ausloeser']);
                if ($ausloeser === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'ausloeser darf nicht leer sein.');
                }
                $payload['ausloeser'] = $ausloeser;
            }
            foreach (['grenzwert', 'rechtsgrundlage', 'beschreibung'] as $optional) {
                if (array_key_exists($optional, $arguments)) {
                    $val = $arguments[$optional];
                    $payload[$optional] = ($val === null || trim((string) $val) === '') ? null : trim((string) $val);
                }
            }
            if (array_key_exists('status', $arguments) && $arguments['status'] !== null) {
                if (!in_array($arguments['status'], ['aktiv', 'archiviert'], true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'status muss aktiv oder archiviert sein.');
                }
                $payload['status'] = $arguments['status'];
            }

            if (empty($payload)) {
                return ToolResult::error('NO_CHANGE', 'Keine Aenderungen uebergeben.');
            }

            $anlass = (new AnlassService())->updateAnlass($anlass, $payload);

            return ToolResult::success([
                'id' => $anlass->id,
                'uuid' => $anlass->uuid,
                'teil' => $anlass->teil,
                'vorsorgeart' => $anlass->vorsorgeart,
                'titel' => $anlass->titel,
                'status' => $anlass->status,
                'team_id' => $anlass->team_id,
                'message' => "Anlass '{$anlass->titel}' erfolgreich aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Anlasses: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['arbmedvv', 'anlaesse', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
