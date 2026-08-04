<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Arbmedvv\Models\Anlass;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class ListAnlaesseTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.anlaesse.GET';
    }

    public function getDescription(): string
    {
        return 'GET /arbmedvv/anlaesse - Listet Vorsorge-Anlaesse. Parameter: team_id (optional), teil (optional: gefahrstoffe|biostoffe|physikalisch|sonstige), vorsorgeart (optional: pflicht|angebot|nachgehend), status (optional: aktiv|archiviert), search/sort/limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                    ],
                    'teil' => [
                        'type' => 'string',
                        'enum' => ['gefahrstoffe', 'biostoffe', 'physikalisch', 'sonstige'],
                        'description' => 'Optional: Filter nach Teil des Anhangs.',
                    ],
                    'vorsorgeart' => [
                        'type' => 'string',
                        'enum' => ['pflicht', 'angebot', 'nachgehend'],
                        'description' => 'Optional: Filter nach Vorsorgeart.',
                    ],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['aktiv', 'archiviert'],
                        'description' => 'Optional: Filter nach Status.',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $query = Anlass::query()->forTeam($teamId);

            if (isset($arguments['teil'])) {
                $query->byTeil($arguments['teil']);
            }
            if (isset($arguments['vorsorgeart'])) {
                $query->byVorsorgeart($arguments['vorsorgeart']);
            }
            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'teil', 'vorsorgeart', 'status', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['titel', 'ausloeser', 'grenzwert', 'rechtsgrundlage']);
            $this->applyStandardSort($query, $arguments, [
                'titel', 'teil', 'vorsorgeart', 'position', 'status', 'created_at', 'updated_at',
            ], 'teil', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(fn (Anlass $a) => $this->serialize($a))->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Anlaesse: ' . $e->getMessage());
        }
    }

    private function serialize(Anlass $a): array
    {
        return [
            'id' => $a->id,
            'uuid' => $a->uuid,
            'teil' => $a->teil,
            'vorsorgeart' => $a->vorsorgeart,
            'titel' => $a->titel,
            'ausloeser' => $a->ausloeser,
            'grenzwert' => $a->grenzwert,
            'rechtsgrundlage' => $a->rechtsgrundlage,
            'beschreibung' => $a->beschreibung,
            'status' => $a->status,
            'position' => $a->position,
            'team_id' => $a->team_id,
            'created_at' => $a->created_at?->toISOString(),
            'updated_at' => $a->updated_at?->toISOString(),
        ];
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['arbmedvv', 'anlaesse', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
