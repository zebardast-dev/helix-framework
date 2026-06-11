<?php

namespace Helix;

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
    public static function boot(array $config = []): Application
    {
        $app = static::createApplication($config);
        static::bootBlade($app);
        static::bootTemplate($app);
        static::bootViewDiscovery($app);

        return $app;
    }

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

    protected static function bootBlade(Application $app): void
    {
        $viewsPath = $app->make('helix.views_path');
        $cachePath = $app->make('helix.cache_path') . '/views';

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        $filesystem = new Filesystem();
        $app->instance('files', $filesystem);

        $blade = new BladeCompiler($filesystem, $cachePath);
        $app->instance('blade.compiler', $blade);
        $app->instance(BladeCompiler::class, $blade);

        $resolver = new EngineResolver();
        $resolver->register('blade', fn() => new CompilerEngine($blade));
        $resolver->register('php', fn() => new \Illuminate\View\Engines\PhpEngine($filesystem));

        $finder    = new FileViewFinder($filesystem, [$viewsPath]);
        $events    = new Dispatcher($app);
        $factory   = new Factory($resolver, $finder, $events);

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
