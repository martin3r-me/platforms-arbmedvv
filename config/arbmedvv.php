<?php

/**
 * ArbMedVV module – configuration
 *
 * Catalog of occupational-health preventive-care occasions from the annex of the ArbMedVV.
 *
 * NOTE: machine identifiers (keys/enum codes) are English; display labels stay German
 * because the app UI is German.
 *
 * @see Platform\Core\PlatformCore::registerModule()
 */

return [
    /**
     * Routing: /arbmedvv/...
     */
    'routing' => [
        'mode'   => env('ARBMEDVV_MODE', 'path'),
        'prefix' => 'arbmedvv',
    ],

    'guard' => 'web',

    /**
     * Main navigation
     */
    'navigation' => [
        'route' => 'arbmedvv.dashboard',
        'icon'  => 'heroicon-o-clipboard-document-check',
        'order' => 100,
    ],

    /**
     * Sidebar
     */
    'sidebar' => [
        [
            'group' => 'ArbMedVV',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'arbmedvv.dashboard',
                    'icon'  => 'heroicon-o-home',
                ],
                [
                    'label' => 'Anlässe',
                    'route' => 'arbmedvv.occasions.index',
                    'icon'  => 'heroicon-o-list-bullet',
                ],
            ],
        ],
    ],

    /**
     * Taxonomies – fixed by law (ArbMedVV annex). Kept here centrally as label maps
     * instead of dedicated tables. Enum CODES are English; display labels stay German.
     * Used by UI and LLM tools.
     */

    // Annex parts 1–4 (section)
    'sections' => [
        'hazardous_substances' => 'Teil 1 – Tätigkeiten mit Gefahrstoffen',
        'biological_agents'    => 'Teil 2 – Tätigkeiten mit biologischen Arbeitsstoffen',
        'physical_agents'      => 'Teil 3 – Tätigkeiten mit physikalischen Einwirkungen',
        'other'                => 'Teil 4 – Sonstige Tätigkeiten',
    ],

    // Short labels (for the narrow sidebar / badges)
    'sections_short' => [
        'hazardous_substances' => 'Gefahrstoffe',
        'biological_agents'    => 'Biologische Arbeitsstoffe',
        'physical_agents'      => 'Physikalische Einwirkungen',
        'other'                => 'Sonstige Tätigkeiten',
    ],

    // Type of preventive care (care_type)
    'care_types' => [
        'mandatory'  => 'Pflichtvorsorge',
        'offered'    => 'Angebotsvorsorge',
        'request'    => 'Wunschvorsorge (§5a)',
        'follow_up'  => 'Nachgehende Vorsorge',
    ],
];
