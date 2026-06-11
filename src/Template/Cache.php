<?php

namespace Helix\Template;

class Cache
{
    protected string $prefix = 'helix_tpl_';
    protected int    $expire = 3600;

    public function get(string $key): mixed
    {
        if (defined('DISABLE_TEMPLATE_CACHE') && DISABLE_TEMPLATE_CACHE) {
            return false;
        }

        $cacheKey = $this->key($key);

        $cached = wp_cache_get($cacheKey, 'helix');

        if ($cached !== false) {
            return $cached;
        }

        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            wp_cache_set($cacheKey, $cached, 'helix', $this->expire);
            return $cached;
        }

        return null;
    }

    public function put(string $key, mixed $value): bool
    {
        if (defined('DISABLE_TEMPLATE_CACHE') && DISABLE_TEMPLATE_CACHE) {
            return false;
        }

        $cacheKey = $this->key($key);

        wp_cache_set($cacheKey, $value, 'helix', $this->expire);
        set_transient($cacheKey, $value, $this->expire);

        return true;
    }

    public function forget(string $key): void
    {
        $cacheKey = $this->key($key);

        wp_cache_delete($cacheKey, 'helix');
        delete_transient($cacheKey);
    }

    protected function key(string $key): string
    {
        return $this->prefix . md5($key);
    }
}
