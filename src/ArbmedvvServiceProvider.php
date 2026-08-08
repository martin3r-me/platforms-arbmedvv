<?php

namespace Platform\Arbmedvv;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ArbmedvvServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arbmedvv.php', 'arbmedvv');
    }

    public function boot(): void
    {
        // Morph alias for dimension links / polymorphic relations (short alias instead of FQCN)
        Relation::morphMap([
            'arbmedvv_occasion' => \Platform\Arbmedvv\Models\Occasion::class,
        ]);

        // Vermengungsgruppen-Provider registrieren (lose Kopplung → der Termin prüft über die Core-Registry).
        if (class_exists(\Platform\Core\Support\CatalogCombinationRegistry::class)) {
            try {
                app(\Platform\Core\Support\CatalogCombinationRegistry::class)
                    ->register(new \Platform\Arbmedvv\Catalog\OccasionCombinationProvider());
            } catch (\Throwable $e) {
            }
        }

        // Step 1: Register module
        if (
            config()->has('arbmedvv.routing') &&
            config()->has('arbmedvv.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'arbmedvv',
                'title'      => 'ArbMedVV',
                'group'      => 'catalog',
                'routing'    => config('arbmedvv.routing'),
                'guard'      => config('arbmedvv.guard'),
                'navigation' => config('arbmedvv.navigation'),
                'sidebar'    => config('arbmedvv.sidebar'),
            ]);
        }

        // Step 2: Routes (only if module registered)
        if (PlatformCore::getModule('arbmedvv')) {
            ModuleRouter::group('arbmedvv', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }

        // Step 3: Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Step 4: Publish config
        $this->publishes([
            __DIR__ . '/../config/arbmedvv.php' => config_path('arbmedvv.php'),
        ], 'config');

        // Step 5: Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'arbmedvv');

        // Step 6: Livewire components (auto-scan)
        $this->registerLivewireComponents();

        // Step 7: LLM Tools
        $this->registerTools();

        // Step 8: Organization graph integration (EntityLinkProvider)
        $this->registerOrganizationIntegration();
    }

    /**
     * Registers the module's MCP/LLM tools.
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Arbmedvv\Tools\ArbmedvvOverviewTool());

            // Occasion CRUD
            $registry->register(new \Platform\Arbmedvv\Tools\ListOccasionsTool());
            $registry->register(new \Platform\Arbmedvv\Tools\GetOccasionTool());
            $registry->register(new \Platform\Arbmedvv\Tools\CreateOccasionTool());
            $registry->register(new \Platform\Arbmedvv\Tools\UpdateOccasionTool());
            $registry->register(new \Platform\Arbmedvv\Tools\DeleteOccasionTool());
        } catch (\Throwable $e) {
            \Log::warning('ArbMedVV: tool registration failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Registers the EntityLinkProvider so Occasion records render richly when linked
     * to organization entities via dimension links. No-op if organization is absent.
     */
    protected function registerOrganizationIntegration(): void
    {
        try {
            resolve(\Platform\Organization\Services\EntityLinkRegistry::class)
                ->register(new \Platform\Arbmedvv\Organization\ArbmedvvEntityLinkProvider());
        } catch (\Throwable $e) {
            // Organization module not loaded — dimension links still work, just without rich rendering.
        }
    }

    /**
     * Registers all Livewire components automatically.
     *
     * File src/Livewire/Occasion/Index.php -> alias arbmedvv.occasion.index
     */
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Arbmedvv\\Livewire';
        $prefix = 'arbmedvv';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
