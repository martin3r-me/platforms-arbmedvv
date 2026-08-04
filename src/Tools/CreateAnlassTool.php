<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Arbmedvv\Services\AnlassService;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class CreateAnlassTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.anlaesse.POST';
    }

    public function getDescription(): string
    {
        return 'POST /arbmedvv/anlaesse - Erstellt einen Vorsorge-Anlass. ERFORDERLICH: teil (gefahrstoffe|biostoffe|physikalisch|sonstige), vorsorgeart (pflicht|angebot|nachgehend), titel, ausloeser. Optional: grenzwert, rechtsgrundlage, beschreibung, status (default: aktiv).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'teil' => [
                    'type' => 'string',
                    'enum' => ['gefahrstoffe', 'biostoffe', 'physikalisch', 'sonstige'],
                    'description' => 'Teil des Anhangs (ERFORDERLICH).',
                ],
                'vorsorgeart' => [
                    'type' => 'string',
                    'enum' => ['pflicht', 'angebot', 'nachgehend'],
                    'description' => 'Art der Vorsorge (ERFORDERLICH).',
                ],
                'titel' => [
                    'type' => 'string',
                    'description' => 'Kurzbezeichnung, z.B. "Feuchtarbeit" (ERFORDERLICH).',
                ],
                'ausloeser' => [
                    'type' => 'string',
                    'description' => 'Wortlaut des auslösenden Gefährdungs-/Expositionstatbestands (ERFORDERLICH).',
                ],
                'grenzwert' => [
                    'type' => 'string',
                    'description' => 'Optional: Schwelle, z.B. "≥ 4 Std./Tag", "85 dB(A)".',
                ],
                'rechtsgrundlage' => [
                    'type' => 'string',
                    'description' => 'Optional: Fundstelle, z.B. "Anhang Teil 1 (2)".',
                ],
                'beschreibung' => [
                    'type' => 'string',
                    'description' => 'Optional: Hinweise/Notizen.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['aktiv', 'archiviert'],
                    'description' => 'Optional: Status. Default: aktiv.',
                ],
            ],
            'required' => ['teil', 'vorsorgeart', 'titel', 'ausloeser'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $teil = (string) ($arguments['teil'] ?? '');
            if (!in_array($teil, ['gefahrstoffe', 'biostoffe', 'physikalisch', 'sonstige'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'teil ist ungueltig.');
            }
            $vorsorgeart = (string) ($arguments['vorsorgeart'] ?? '');
            if (!in_array($vorsorgeart, ['pflicht', 'angebot', 'nachgehend'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'vorsorgeart ist ungueltig.');
            }
            $titel = trim((string) ($arguments['titel'] ?? ''));
            if ($titel === '') {
                return ToolResult::error('VALIDATION_ERROR', 'titel ist erforderlich.');
            }
            $ausloeser = trim((string) ($arguments['ausloeser'] ?? ''));
            if ($ausloeser === '') {
                return ToolResult::error('VALIDATION_ERROR', 'ausloeser ist erforderlich.');
            }
            $status = $arguments['status'] ?? 'aktiv';
            if (!in_array($status, ['aktiv', 'archiviert'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'status muss aktiv oder archiviert sein.');
            }

            $anlass = (new AnlassService())->createAnlass([
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
                'teil' => $teil,
                'vorsorgeart' => $vorsorgeart,
                'titel' => $titel,
                'ausloeser' => $ausloeser,
                'grenzwert' => isset($arguments['grenzwert']) && $arguments['grenzwert'] !== '' ? trim((string) $arguments['grenzwert']) : null,
                'rechtsgrundlage' => isset($arguments['rechtsgrundlage']) && $arguments['rechtsgrundlage'] !== '' ? trim((string) $arguments['rechtsgrundlage']) : null,
                'beschreibung' => isset($arguments['beschreibung']) && $arguments['beschreibung'] !== '' ? trim((string) $arguments['beschreibung']) : null,
                'status' => $status,
            ]);

            return ToolResult::success([
                'id' => $anlass->id,
                'uuid' => $anlass->uuid,
                'teil' => $anlass->teil,
                'vorsorgeart' => $anlass->vorsorgeart,
                'titel' => $anlass->titel,
                'status' => $anlass->status,
                'team_id' => $anlass->team_id,
                'message' => "Anlass '{$anlass->titel}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Anlasses: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['arbmedvv', 'anlaesse', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
