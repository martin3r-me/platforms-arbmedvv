<?php

/**
 * ArbMedVV Modul – Konfiguration
 *
 * Katalog der arbeitsmedizinischen Vorsorge-Anlässe nach dem Anhang der ArbMedVV.
 * Struktur folgt dem module-template (kanonische Referenz für nx-Module).
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
     * Haupt-Navigation
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
                    'route' => 'arbmedvv.anlaesse.index',
                    'icon'  => 'heroicon-o-list-bullet',
                ],
            ],
        ],
    ],

    /**
     * Taxonomien – fix per Gesetz (Anhang ArbMedVV), daher hier zentral als
     * Label-Maps statt eigener Tabellen. Genutzt von UI und LLM-Tools.
     */

    // Teile 1–4 des Anhangs
    'teile' => [
        'gefahrstoffe' => 'Teil 1 – Tätigkeiten mit Gefahrstoffen',
        'biostoffe'    => 'Teil 2 – Tätigkeiten mit biologischen Arbeitsstoffen',
        'physikalisch' => 'Teil 3 – Tätigkeiten mit physikalischen Einwirkungen',
        'sonstige'     => 'Teil 4 – Sonstige Tätigkeiten',
    ],

    // Kurzlabels (für schmale Sidebar / Badges)
    'teile_kurz' => [
        'gefahrstoffe' => 'Gefahrstoffe',
        'biostoffe'    => 'Biologische Arbeitsstoffe',
        'physikalisch' => 'Physikalische Einwirkungen',
        'sonstige'     => 'Sonstige Tätigkeiten',
    ],

    // Art der Vorsorge
    'vorsorgearten' => [
        'pflicht'    => 'Pflichtvorsorge',
        'angebot'    => 'Angebotsvorsorge',
        'nachgehend' => 'Nachgehende Vorsorge',
    ],
];
