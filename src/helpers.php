<?php

use Helix\Query\QueryBuilder;
use Illuminate\Container\Container;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('app')) {
    function app($abstract = null)
    {
        $container = Container::getInstance();

        if ($abstract === null) {
            return $container;
        }

        return $container->get($abstract);
    }
}

if (!function_exists('view')) {
    function view($view = null, $data = [], $mergeData = [])
    {
        $factory = app('view');

        if ($view) {
            return $factory->make($view, $data, $mergeData);
        }

        return $factory;
    }
}

if (!function_exists('theme_option')) {
    function theme_option($key = null, $default = null)
    {
        $options = defined('OPTIONS') ? OPTIONS : [];

        if ($key === null) {
            return $options;
        }

        return $options[$key] ?? $default;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return defined('ASSETS_URI')
            ? ASSETS_URI . '/' . ltrim($path, '/')
            : '';
    }
}

if (!function_exists('image_url')) {
    function image_url($image, string $size = 'full'): string
    {
        if (!$image) {
            return defined('NO_IMAGE') ? NO_IMAGE : '';
        }

        if (is_numeric($image)) {
            $img = wp_get_attachment_image_src($image, $size);
            return $img ? $img[0] : '';
        }

        if (is_array($image)) {
            if (isset($image['sizes'][$size])) return $image['sizes'][$size];
            if (isset($image['url']))          return $image['url'];
        }

        if (is_string($image)) {
            return $image;
        }

        return '';
    }
}

if (!function_exists('svg')) {
    function svg(string $name): string
    {
        $file = defined('THEME_DIR')
            ? THEME_DIR . '/assets/svg/' . $name . '.svg'
            : get_template_directory() . '/assets/svg/' . $name . '.svg';

        return file_exists($file) ? file_get_contents($file) : '';
    }
}

if (!function_exists('elementor_template')) {
    function elementor_template(int $id): void
    {
        if (!$id || !did_action('elementor/loaded')) {
            return;
        }

        echo \Elementor\Plugin::instance()
            ->frontend
            ->get_builder_content_for_display($id);
    }
}

if (!function_exists('dd')) {
    function dd(...$args): void
    {
        echo '<pre>';
        foreach ($args as $arg) {
            var_dump($arg);
        }
        echo '</pre>';
        die();
    }
}

if (!function_exists('query')) {
    function query(): QueryBuilder
    {
        return new QueryBuilder();
    }
}

if (!function_exists('repeatedView')) {
    function repeatedView(string $base, string $name, int $times = 1)
    {
        return view($base . '.' . implode('.', array_fill(0, $times, $name)));
    }
}
