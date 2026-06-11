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

    public function on(string|array $conditions): static
    {
        $conditions = (array) $conditions;

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
            if ($c === 'global')                                           return true;
            if ($c === 'home'   && (is_front_page() || is_home()))        return true;
            if ($c === 'single' && is_singular())                         return true;
            if (str_starts_with($c, 'single-') && is_singular(substr($c, 7))) return true;
            if (str_starts_with($c, 'page-')   && is_page(substr($c, 5))) return true;
            if (function_exists('is_checkout') && $c === 'woocommerce-checkout' && is_checkout()) return true;
        }

        return false;
    }

    protected static function builder(string $handle, string $type): static
    {
        $instance         = new static();
        $instance->handle = $handle;
        $instance->type   = $type;
        return $instance;
    }
}
