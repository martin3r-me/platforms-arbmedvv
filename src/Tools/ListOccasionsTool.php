<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Arbmedvv\Models\Occasion;
use Platform\Arbmedvv\Tools\Concerns\ResolvesArbmedvvTeam;

class ListOccasionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesArbmedvvTeam;

    public function getName(): string
    {
        return 'arbmedvv.occasions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /arbmedvv/occasions - Lists preventive-care occasions. Params: team_id (optional), section (optional: hazardous_substances|biological_agents|physical_agents|other), care_type (optional: mandatory|offered|follow_up), status (optional: active|archived), search/sort/limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: team id. Default: current team from context.',
                    ],
                    'section' => [
                        'type' => 'string',
                        'enum' => ['hazardous_substances', 'biological_agents', 'physical_agents', 'other'],
                        'description' => 'Optional: filter by annex part.',
                    ],
                    'care_type' => [
                        'type' => 'string',
                        'enum' => ['mandatory', 'offered', 'follow_up'],
                        'description' => 'Optional: filter by care type.',
                    ],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['active', 'archived'],
                        'description' => 'Optional: filter by status.',
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

            $query = Occasion::query()->forTeam($teamId);

            if (isset($arguments['section'])) {
                $query->bySection($arguments['section']);
            }
            if (isset($arguments['care_type'])) {
                $query->byCareType($arguments['care_type']);
            }
            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'section', 'care_type', 'status', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'trigger', 'threshold', 'legal_basis']);
            $this->applyStandardSort($query, $arguments, [
                'title', 'section', 'care_type', 'position', 'status', 'created_at', 'updated_at',
            ], 'section', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(fn (Occasion $o) => $this->serialize($o))->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading occasions: ' . $e->getMessage());
        }
    }

    private function serialize(Occasion $o): array
    {
        return [
            'id' => $o->id,
            'uuid' => $o->uuid,
            'section' => $o->section,
            'care_type' => $o->care_type,
            'title' => $o->title,
            'trigger' => $o->trigger,
            'threshold' => $o->threshold,
            'legal_basis' => $o->legal_basis,
            'description' => $o->description,
            'status' => $o->status,
            'position' => $o->position,
            'team_id' => $o->team_id,
            'created_at' => $o->created_at?->toISOString(),
            'updated_at' => $o->updated_at?->toISOString(),
        ];
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['arbmedvv', 'occasions', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
