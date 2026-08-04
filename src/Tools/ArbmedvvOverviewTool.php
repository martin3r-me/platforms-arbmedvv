<?php

namespace Platform\Arbmedvv\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ArbmedvvOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'arbmedvv.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /arbmedvv/overview - Zeigt Uebersicht ueber das ArbMedVV-Modul (Konzept, Datenmodell, Taxonomie, Tools). Das Modul ist ein team-weiter Katalog der arbeitsmedizinischen Vorsorge-Anlaesse nach dem Anhang der ArbMedVV.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            return ToolResult::success([
                'module' => 'arbmedvv',
                'scope' => [
                    'team_scoped' => true,
                    'team_id_source' => 'ToolContext.team bzw. team_id Parameter',
                ],
                'concepts' => [
                    'anlass' => [
                        'model' => 'Platform\\Arbmedvv\\Models\\Anlass',
                        'table' => 'arbmedvv_anlaesse',
                        'key_fields' => ['id', 'uuid', 'teil', 'vorsorgeart', 'titel', 'ausloeser', 'grenzwert', 'rechtsgrundlage', 'beschreibung', 'status', 'position', 'team_id'],
                        'note' => 'Ein Datensatz = ein Vorsorge-Anlass aus dem Anhang der ArbMedVV.',
                    ],
                ],
                'taxonomy' => [
                    'teil' => config('arbmedvv.teile'),
                    'vorsorgeart' => config('arbmedvv.vorsorgearten'),
                    'status' => ['aktiv', 'archiviert'],
                ],
                'fields' => [
                    'titel' => 'Kurzbezeichnung, z.B. "Feuchtarbeit".',
                    'ausloeser' => 'Wortlaut des auslösenden Gefährdungs-/Expositionstatbestands.',
                    'grenzwert' => 'Optionale Schwelle, z.B. "≥ 4 Std./Tag", "85 dB(A)".',
                    'rechtsgrundlage' => 'Fundstelle, z.B. "Anhang Teil 1 (2)".',
                ],
                'related_tools' => [
                    'list' => 'arbmedvv.anlaesse.GET',
                    'get' => 'arbmedvv.anlass.GET',
                    'create' => 'arbmedvv.anlaesse.POST',
                    'update' => 'arbmedvv.anlaesse.PUT',
                    'delete' => 'arbmedvv.anlaesse.DELETE',
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der ArbMedVV-Uebersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'arbmedvv'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
