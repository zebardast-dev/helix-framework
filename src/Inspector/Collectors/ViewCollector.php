<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;

class ViewCollector implements CollectorInterface
{
    private string $wpTemplate = '';
    private array  $rendered   = [];

    public function name(): string  { return 'views'; }
    public function title(): string { return 'Views'; }
    public function icon(): string  { return '🎨'; }

    public function boot(): void
    {
        add_filter('template_include', function ($template) {
            $this->wpTemplate = $template;
            return $template;
        }, 9999);

        // Track Blade/include files via ob_start is complex — hook into a simpler signal
        add_filter('the_content', function ($content) {
            $this->rendered[] = 'the_content';
            return $content;
        });
    }

    public function collect(): array
    {
        global $post;

        $pageType = $this->detectPageType();

        $cacheDir   = defined('THEME_DIR') ? constant('THEME_DIR') . '/storage/cache/views' : '';
        $cacheFiles = ($cacheDir && is_dir($cacheDir))
            ? count(glob($cacheDir . '/*.php') ?: [])
            : 0;

        $conditionals = array_filter([
            'is_front_page' => is_front_page(),
            'is_home'       => is_home(),
            'is_single'     => is_single(),
            'is_page'       => is_page(),
            'is_archive'    => is_archive(),
            'is_search'     => is_search(),
            'is_404'        => is_404(),
            'is_admin'      => is_admin(),
        ]);

        return [
            'page_type'    => $pageType,
            'post_type'    => $post->post_type ?? null,
            'template'     => $this->wpTemplate ? basename($this->wpTemplate) : null,
            'template_dir' => $this->wpTemplate ? dirname($this->wpTemplate) : null,
            'conditionals' => array_keys($conditionals),
            'cache_files'  => $cacheFiles,
            'cache_dir'    => $cacheDir ?: null,
        ];
    }

    private function detectPageType(): string
    {
        if (is_front_page()) return 'front-page';
        if (is_home())       return 'home';
        if (is_404())        return '404';
        if (is_search())     return 'search';
        if (is_single())     return 'single';
        if (is_page())       return 'page';
        if (is_archive())    return 'archive';
        if (is_admin())      return 'admin';
        return 'unknown';
    }
}
