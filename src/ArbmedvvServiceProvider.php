<?php

namespace Platform\Arbmedvv;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        // Step 1: Register module
        if (
            config()->has('arbmedvv.routing') &&
            config()->has('arbmedvv.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'arbmedvv',
                'title'      => 'ArbMedVV',
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
    }

    /**
     * Registriert die MCP/LLM-Tools des Moduls.
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Arbmedvv\Tools\ArbmedvvOverviewTool());

            // Anlass CRUD
            $registry->register(new \Platform\Arbmedvv\Tools\ListAnlaesseTool());
            $registry->register(new \Platform\Arbmedvv\Tools\GetAnlassTool());
            $registry->register(new \Platform\Arbmedvv\Tools\CreateAnlassTool());
            $registry->register(new \Platform\Arbmedvv\Tools\UpdateAnlassTool());
            $registry->register(new \Platform\Arbmedvv\Tools\DeleteAnlassTool());
        } catch (\Throwable $e) {
            \Log::warning('ArbMedVV: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Registriert alle Livewire-Komponenten automatisch.
     *
     * Datei src/Livewire/Anlass/Index.php -> Alias arbmedvv.anlass.index
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
