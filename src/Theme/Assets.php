<?php

namespace Helix\Theme;

if (!defined('ABSPATH')) exit;

class Assets
{
    protected static array $scripts     = [];
    protected static array $styles      = [];
    protected static array $fonts       = [];
    protected static array $scriptAttrs = [];
    protected static array $preloads    = [];
    protected static bool  $booted      = false;

    protected string $handle;
    protected string $type;

    public function __construct()
    {
        if (self::$booted) return;
        self::$booted = true;

        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
        add_filter('script_loader_tag',  [$this, 'applyScriptAttrs'], 10, 2);
        add_action('wp_head',            [$this, 'printPreloads'], 1);
    }

    /* -------------------------------------------------------------- */

    public static function script(string $handle, string $src, array $deps = [], ?string $ver = null, bool $inFooter = true): static
    {
        self::$scripts[$handle] = [
            'src'        => $src,
            'deps'       => $deps,
            'ver'        => $ver,
            'footer'     => $inFooter,
            'conditions' => [],
            'attrs'      => [],
        ];

        return static::builder($handle, 'script');
    }

    public static function style(string $handle, string $src, array $deps = [], ?string $ver = null, string $media = 'all'): static
    {
        self::$styles[$handle] = [
            'src'        => $src,
            'deps'       => $deps,
            'ver'        => $ver,
            'media'      => $media,
            'conditions' => [],
            'attrs'      => [],
        ];

        return static::builder($handle, 'style');
    }

    public static function font(string $handle, string $path): static
    {
        self::$fonts[$handle] = ['src' => $path, 'type' => 'font/woff2', 'attrs' => []];
        return static::builder($handle, 'font');
    }

    /* -------------------------------------------------------------- */

    public function on(string|array|\Closure $conditions): static
    {
        $conditions = is_array($conditions) ? $conditions : [$conditions];

        if ($this->type === 'script') {
            self::$scripts[$this->handle]['conditions'] = array_merge(
                self::$scripts[$this->handle]['conditions'], $conditions
            );
        } else {
            self::$styles[$this->handle]['conditions'] = array_merge(
                self::$styles[$this->handle]['conditions'], $conditions
            );
        }

        return $this;
    }

    public function defer(): static    { return $this->attr('defer', true); }
    public function async(): static    { return $this->attr('async', true); }
    public function module(): static   { return $this->attr('type', 'module'); }
    public function nomodule(): static { return $this->attr('nomodule', true); }

    public function media(string $media): static
    {
        if ($this->type === 'style') {
            self::$styles[$this->handle]['media'] = $media;
        }
        return $this;
    }

    public function preload(?string $as = null): static
    {
        $as  = $as ?? match ($this->type) { 'script' => 'script', 'style' => 'style', 'font' => 'font', default => null };
        $src = match ($this->type) {
            'script' => self::$scripts[$this->handle]['src'],
            'style'  => self::$styles[$this->handle]['src'],
            'font'   => self::$fonts[$this->handle]['src'],
            default  => null,
        };

        if ($as && $src) {
            self::$preloads[] = [
                'src'        => $src,
                'as'         => $as,
                'type'       => $as === 'font' ? 'font/woff2' : null,
                'crossorigin'=> $as === 'font' ? 'anonymous'  : null,
            ];
        }

        return $this;
    }

    public function attr(string $key, mixed $value): static
    {
        match ($this->type) {
            'script' => self::$scripts[$this->handle]['attrs'][$key] = $value,
            'style'  => self::$styles[$this->handle]['attrs'][$key]  = $value,
            'font'   => self::$fonts[$this->handle]['attrs'][$key]   = $value,
            default  => null,
        };
        return $this;
    }

    /* -------------------------------------------------------------- */

    public function enqueue(): void
    {
        foreach (self::$styles as $handle => $style) {
            if (!$this->shouldLoad($style['conditions'])) continue;

            $path = defined('ASSETS_DIR') ? ASSETS_DIR . '/' . $style['src'] : '';
            $ver  = $style['ver'] ?: ($path && file_exists($path) ? filemtime($path) : null);

            wp_enqueue_style($handle, asset($style['src']), $style['deps'], $ver, $style['media']);
        }

        foreach (self::$scripts as $handle => $script) {
            if (!$this->shouldLoad($script['conditions'])) continue;

            $path = defined('ASSETS_DIR') ? ASSETS_DIR . '/' . $script['src'] : '';
            $ver  = $script['ver'] ?: ($path && file_exists($path) ? filemtime($path) : null);

            wp_enqueue_script($handle, asset($script['src']), $script['deps'], $ver, $script['footer']);
            self::$scriptAttrs[$handle] = $script['attrs'];
        }
    }

    public function applyScriptAttrs(string $tag, string $handle): string
    {
        if (!isset(self::$scriptAttrs[$handle])) return $tag;

        foreach (self::$scriptAttrs[$handle] as $attr => $val) {
            $tag = $val === true
                ? str_replace('<script ', "<script {$attr} ", $tag)
                : str_replace('<script ', "<script {$attr}=\"{$val}\" ", $tag);
        }

        return $tag;
    }

    public function printPreloads(): void
    {
        foreach (self::$preloads as $preload) {
            $url  = asset($preload['src']);
            $html = '<link rel="preload" href="' . $url . '" as="' . $preload['as'] . '"';

            if (!empty($preload['type']))       $html .= ' type="' . $preload['type'] . '"';
            if (!empty($preload['crossorigin'])) $html .= ' crossorigin="' . $preload['crossorigin'] . '"';

            echo $html . ">\n";
        }
    }

    /* -------------------------------------------------------------- */

    protected function shouldLoad(array $conditions): bool
    {
        if (empty($conditions)) return true;

        foreach ($conditions as $c) {
            if ($this->matchCondition($c)) return true;
        }

        return false;
    }

    protected function matchCondition(mixed $c): bool
    {
        if ($c instanceof \Closure) {
            return (bool) $c();
        }

        if (!is_string($c)) return false;

        return match (true) {

            // ── Global ──────────────────────────────────────────────
            $c === 'global'   => true,

            // ── Home ────────────────────────────────────────────────
            $c === 'home'     => is_front_page() || is_home(),

            // ── Singular ────────────────────────────────────────────
            $c === 'single'   => is_singular(),

            // ── Archives ────────────────────────────────────────────
            $c === 'archive'  => is_archive(),
            $c === 'search'   => is_search(),
            $c === '404'      => is_404(),
            $c === 'category' => is_category(),
            $c === 'tag'      => is_tag(),

            // ── WooCommerce ─────────────────────────────────────────
            $c === 'woocommerce-shop'     => function_exists('is_shop')     && is_shop(),
            $c === 'woocommerce-cart'     => function_exists('is_cart')     && is_cart(),
            $c === 'woocommerce-checkout' => function_exists('is_checkout') && is_checkout(),

            // ── single-{post_type} ──────────────────────────────────
            str_starts_with($c, 'single-')   => is_singular(substr($c, 7)),

            // ── page-{slug|id} ──────────────────────────────────────
            str_starts_with($c, 'page-')     => is_page(substr($c, 5)),

            // ── archive-{post_type} ─────────────────────────────────
            str_starts_with($c, 'archive-')  => is_post_type_archive(substr($c, 8)),

            // ── category-{slug} ─────────────────────────────────────
            str_starts_with($c, 'category-') => is_category(substr($c, 9)),

            // ── tag-{slug} ──────────────────────────────────────────
            str_starts_with($c, 'tag-')      => is_tag(substr($c, 4)),

            // ── tax-{taxonomy} or tax-{taxonomy}:{term} ─────────────
            str_starts_with($c, 'tax-')      => $this->matchTaxonomy(substr($c, 4)),

            // ── id-{id} ─────────────────────────────────────────────
            str_starts_with($c, 'id-')       => get_queried_object_id() === (int) substr($c, 3),

            default => false,
        };
    }

    protected function matchTaxonomy(string $value): bool
    {
        if (str_contains($value, ':')) {
            [$taxonomy, $term] = explode(':', $value, 2);
            return is_tax($taxonomy, $term);
        }

        return is_tax($value);
    }

    protected static function builder(string $handle, string $type): static
    {
        $instance         = new static();
        $instance->handle = $handle;
        $instance->type   = $type;
        return $instance;
    }
}
