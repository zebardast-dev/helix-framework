<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;

class ViewCollector implements CollectorInterface
{
    private string $bladeView  = '';  // e.g. "single.post"
    private string $wpTemplate = '';  // the actual PHP file WP included

    public function name(): string  { return 'views'; }
    public function title(): string { return 'Views'; }
    public function icon(): string  { return '🎨'; }

    public function boot(): void
    {
        // Helix Loader sets 'blade_view' query var at priority 100.
        // We run at 9999, so it's already set when we arrive.
        add_filter('template_include', function (string $template): string {
            $this->wpTemplate = $template;
            $this->bladeView  = (string) get_query_var('blade_view', '');
            return $template;
        }, 9999);
    }

    public function collect(): array
    {
        global $post;

        $conditionals = array_keys(array_filter([
            'is_front_page' => is_front_page(),
            'is_home'       => is_home(),
            'is_single'     => is_single(),
            'is_page'       => is_page(),
            'is_archive'    => is_archive(),
            'is_search'     => is_search(),
            'is_404'        => is_404(),
            'is_admin'      => is_admin(),
        ]));

        // Convert blade view notation to a readable file path.
        // "single.post" → "resources/views/single/post.blade.php"
        $bladeFile = $this->bladeView
            ? 'resources/views/' . str_replace('.', '/', $this->bladeView) . '.blade.php'
            : null;

        // Total compiled templates in cache (informational — not per-page)
        $cacheDir   = defined('THEME_DIR') ? constant('THEME_DIR') . '/storage/cache/views' : '';
        $totalCache = ($cacheDir && is_dir($cacheDir))
            ? count(glob($cacheDir . '/*.php') ?: [])
            : null;

        return [
            'page_type'    => $this->detectPageType(),
            'post_type'    => $post->post_type ?? null,
            'blade_view'   => $this->bladeView  ?: null,   // raw blade name: "single.post"
            'blade_file'   => $bladeFile,                   // file path: "resources/views/..."
            'total_cache'  => $totalCache,                  // total compiled Blade files on disk
            'conditionals' => $conditionals,
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
