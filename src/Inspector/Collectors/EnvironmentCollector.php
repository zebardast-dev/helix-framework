<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;

class EnvironmentCollector implements CollectorInterface
{
    public function name(): string  { return 'environment'; }
    public function title(): string { return 'Environment'; }
    public function icon(): string  { return '🌐'; }

    public function boot(): void {}

    public function collect(): array
    {
        global $wpdb;

        return [
            'wordpress' => $this->wordpress(),
            'php'       => $this->php(),
            'server'    => $this->server(),
            'database'  => $this->database($wpdb),
            'theme'     => $this->theme(),
            'plugins'   => $this->plugins(),
            'constants' => $this->constants(),
        ];
    }

    private function wordpress(): array
    {
        return [
            'version'    => get_bloginfo('version'),
            'site_url'   => get_site_url(),
            'home_url'   => get_home_url(),
            'multisite'  => is_multisite(),
            'language'   => get_bloginfo('language'),
            'charset'    => get_bloginfo('charset'),
        ];
    }

    private function php(): array
    {
        $extensions = get_loaded_extensions();
        sort($extensions);

        $key = ['mbstring', 'gd', 'imagick', 'curl', 'zip', 'intl', 'opcache', 'redis', 'memcached', 'xdebug'];
        $notable = [];
        foreach ($key as $ext) {
            $notable[$ext] = in_array($ext, $extensions, true);
        }

        return [
            'version'        => PHP_VERSION,
            'sapi'           => PHP_SAPI,
            'memory_limit'   => ini_get('memory_limit'),
            'max_exec_time'  => (int) ini_get('max_execution_time'),
            'upload_max'     => ini_get('upload_max_filesize'),
            'post_max'       => ini_get('post_max_size'),
            'extensions'     => $notable,
        ];
    }

    private function server(): array
    {
        return [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'os'       => PHP_OS_FAMILY,
            'hostname' => gethostname() ?: 'unknown',
            'https'    => is_ssl(),
        ];
    }

    private function database(\wpdb $wpdb): array
    {
        $version = $wpdb->get_var('SELECT VERSION()');

        return [
            'version' => $version,
            'prefix'  => $wpdb->prefix,
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
        ];
    }

    private function theme(): array
    {
        $theme  = wp_get_theme();
        $parent = $theme->parent();

        return [
            'name'        => $theme->get('Name'),
            'version'     => $theme->get('Version'),
            'template'    => get_template(),
            'stylesheet'  => get_stylesheet(),
            'parent'      => $parent ? $parent->get('Name') : null,
            'parent_ver'  => $parent ? $parent->get('Version') : null,
        ];
    }

    private function plugins(): array
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all    = get_plugins();
        $active = (array) get_option('active_plugins', []);
        $result = [];

        foreach ($active as $file) {
            if (!isset($all[$file])) continue;
            $p = $all[$file];
            $result[] = [
                'name'    => $p['Name'],
                'version' => $p['Version'],
                'file'    => $file,
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }

    private function constants(): array
    {
        $keys = [
            'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY',
            'SCRIPT_DEBUG', 'WP_CACHE', 'CONCATENATE_SCRIPTS',
            'SAVEQUERIES', 'APP_DEBUG',
        ];

        $out = [];
        foreach ($keys as $k) {
            $out[$k] = defined($k) ? constant($k) : null;
        }

        return $out;
    }
}
