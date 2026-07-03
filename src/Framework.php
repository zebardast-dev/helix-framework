<?php

namespace Helix;

use Helix\Config\Config;
use Helix\Config\EnvLoader;
use Helix\Foundation\Application;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class Framework
{
    /**
     * Bootstrap the framework from a theme/project base path.
     *
     * Loads .env, all config/ files, and boots every service declared in
     * config('app.services'), config('app.providers'), etc.
     * This is the recommended entry point from functions.php.
     */
    public static function create(string $basePath): Application
    {
        $app = static::makeApplication($basePath);

        static::loadEnvironment($app);
        static::loadConfiguration($app);
        static::bootBlade($app);
        static::bootTemplate($app);
        static::bootViewDiscovery($app);
        static::bootServices($app);

        return $app;
    }

    /**
     * Legacy entry point — accepts a manual config array.
     * Kept for backward compatibility; prefer Framework::create().
     */
    public static function boot(array $config = []): Application
    {
        $app = static::createApplication($config);
        static::bootBlade($app);
        static::bootTemplate($app);
        static::bootViewDiscovery($app);

        return $app;
    }

    // -------------------------------------------------------------------------
    // create() internals
    // -------------------------------------------------------------------------

    protected static function makeApplication(string $basePath): Application
    {
        $app = new Application($basePath);

        $app->instance('app', $app);
        $app->instance(Container::class, $app);
        $app->instance(ApplicationContract::class, $app);
        Container::setInstance($app);

        return $app;
    }

    protected static function loadEnvironment(Application $app): void
    {
        EnvLoader::load($app->basePath('.env'));
    }

    protected static function loadConfiguration(Application $app): void
    {
        Config::load($app->paths()->config());

        // Allow config/app.php to override default path conventions
        $paths = config('app.paths', []);

        $app->instance('helix.views_path',          $paths['views']               ?? $app->paths()->views());
        $app->instance('helix.cache_path',           $paths['cache']               ?? $app->paths()->cache());
        $app->instance('helix.components_path',      $paths['components']          ?? $app->paths()->app('View/Components'));
        $app->instance('helix.components_namespace', $paths['components_namespace'] ?? 'App\\View\\Components\\');
        $app->instance('helix.composers_path',       $paths['composers']           ?? $app->paths()->app('View/Composers'));
        $app->instance('helix.composers_namespace',  $paths['composers_namespace']  ?? 'App\\View\\Composers\\');
        $app->instance('helix.template_file',        $app->basePath('bootstrap/view.php'));
    }

    /**
     * Boot all services, providers, and modules declared in config/app.php.
     *
     * Keys read from config:
     *   app.services         — classes instantiated immediately via $app->make()
     *   app.upload_handlers  — Helix\Media\FileUploader handler classes
     *   app.post_types       — post type classes registered on 'init'
     *   app.providers        — classes with ->register() called on after_setup_theme
     *   app.on_switch_theme  — class with static ::run() called on after_switch_theme
     *   app.modules          — optional module flags (elementor, woocommerce, …)
     *   inspector.*          — Inspector panel config
     */
    protected static function bootServices(Application $app): void
    {
        // Core services (self-register via constructors)
        foreach (config('app.services', []) as $service) {
            $app->make($service);
        }

        // File upload handlers
        $handlers = config('app.upload_handlers', []);
        if (!empty($handlers)) {
            $uploader = $app->make(\Helix\Media\FileUploader::class);
            foreach ($handlers as $handlerClass) {
                $uploader->allow(new $handlerClass());
            }
            $uploader->register();
        }

        // Custom post types — registered on WordPress init hook
        $postTypes = config('app.post_types', []);
        if (!empty($postTypes)) {
            add_action('init', static function () use ($app, $postTypes) {
                foreach ($postTypes as $class) {
                    $app->make($class)->register();
                }
                // Flush rewrite rules only when the list changes
                $hash = md5(serialize($postTypes));
                if (get_option('_helix_pt_hash') !== $hash) {
                    flush_rewrite_rules(false);
                    update_option('_helix_pt_hash', $hash);
                }
            });
        }

        // WordPress providers — booted on after_setup_theme
        $providers = config('app.providers', []);
        if (!empty($providers)) {
            add_action('after_setup_theme', static function () use ($app, $providers) {
                foreach ($providers as $provider) {
                    $app->make($provider)->register();
                }
            });
        }

        // One-time theme-switch handler (e.g. InitialSetup)
        if ($onSwitch = config('app.on_switch_theme')) {
            add_action('after_switch_theme', [$onSwitch, 'run']);
        }

        // Inspector (gated by config + WP_DEBUG, never in production)
        if (config('inspector.enabled', false)) {
            \Helix\Inspector\Inspector::boot(config('inspector.collectors', []));
        }

        // Optional Elementor module
        if (config('app.modules.elementor.enabled', false) && did_action('elementor/loaded')) {
            $app->make(\Helix\Modules\Elementor\Elementor::class);
        }
    }

    // -------------------------------------------------------------------------
    // boot() legacy internals (kept for backward compatibility)
    // -------------------------------------------------------------------------

    /** @deprecated Use Framework::create() instead */
    protected static function createApplication(array $config): Application
    {
        $app = new Application();

        $app->instance('app', $app);
        $app->instance(Container::class, $app);
        $app->instance(ApplicationContract::class, $app);
        Container::setInstance($app);

        $defaults = [
            'views_path'           => get_template_directory() . '/resources/views',
            'cache_path'           => get_template_directory() . '/storage/cache',
            'components_path'      => get_template_directory() . '/app/View/Components',
            'components_namespace' => 'App\\View\\Components\\',
            'composers_path'       => get_template_directory() . '/app/View/Composers',
            'composers_namespace'  => 'App\\View\\Composers\\',
        ];

        foreach (array_merge($defaults, $config) as $key => $value) {
            $app->instance("helix.{$key}", $value);
        }

        return $app;
    }

    // -------------------------------------------------------------------------
    // Shared boot steps (used by both create() and boot())
    // -------------------------------------------------------------------------

    protected static function bootBlade(Application $app): void
    {
        $viewsPath = $app->make('helix.views_path');
        $cachePath = $app->make('helix.cache_path') . '/views';

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($cachePath);
        $app->instance('files', $filesystem);

        $blade = new BladeCompiler($filesystem, $cachePath);
        $app->instance('blade.compiler', $blade);
        $app->instance(BladeCompiler::class, $blade);

        $resolver = new EngineResolver();
        $resolver->register('blade', fn() => new CompilerEngine($blade));
        $resolver->register('php', fn() => new \Illuminate\View\Engines\PhpEngine($filesystem));

        $finder  = new FileViewFinder($filesystem, [$viewsPath]);
        $events  = new Dispatcher($app);
        $factory = new Factory($resolver, $finder, $events);

        $factory->setContainer($app);

        $app->instance('view', $factory);
        $app->instance(\Illuminate\Contracts\View\Factory::class, $factory);
        $app->instance(Factory::class, $factory);
    }

    protected static function bootTemplate(Application $app): void
    {
        (new Template\Loader())->boot();
    }

    protected static function bootViewDiscovery(Application $app): void
    {
        add_action('template_redirect', function () {
            if (
                is_admin() ||
                (defined('DOING_AJAX') && DOING_AJAX) ||
                (defined('REST_REQUEST') && REST_REQUEST) ||
                (function_exists('wp_is_json_request') && wp_is_json_request())
            ) {
                return;
            }

            View\ComponentDiscovery::register();
            (new View\ComposerDiscovery())->register();
        }, 1);
    }
}
