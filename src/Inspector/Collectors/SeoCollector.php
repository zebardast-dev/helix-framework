<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;

class SeoCollector implements CollectorInterface
{
    public function name(): string  { return 'seo'; }
    public function title(): string { return 'SEO'; }
    public function icon(): string  { return '🔍'; }

    public function boot(): void {}

    public function collect(): array
    {
        global $post;

        $checks = [];
        $score  = 100;

        // Title
        $title    = wp_get_document_title();
        $titleLen = mb_strlen($title);
        $titleOk  = $titleLen >= 30 && $titleLen <= 60;
        $checks['title'] = [
            'value'  => $title,
            'length' => $titleLen,
            'status' => $titleOk ? 'ok' : 'warn',
            'note'   => $titleOk ? 'Good length (30–60 chars)' : ($titleLen < 30 ? 'Too short — aim for 30+ chars' : 'Too long — trim to 60 chars'),
        ];
        if (!$titleOk) $score -= 15;

        // Meta description (WP has none natively — check _yoast_wpseo_metadesc or _rank_math_description)
        $desc = '';
        if ($post) {
            $desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true)
                ?: get_post_meta($post->ID, '_rank_math_description', true)
                ?: get_post_meta($post->ID, '_meta_description', true)
                ?: '';
        }
        $descLen = mb_strlen($desc);
        $descOk  = $desc && $descLen >= 70 && $descLen <= 160;
        $checks['description'] = [
            'value'  => $desc ?: null,
            'length' => $descLen,
            'status' => $descOk ? 'ok' : ($desc ? 'warn' : 'error'),
            'note'   => !$desc
                ? 'Missing — add _meta_description post meta or use Yoast/RankMath'
                : ($descOk ? 'Good length (70–160 chars)' : ($descLen < 70 ? 'Too short — aim for 70+ chars' : 'Too long — trim to 160 chars')),
        ];
        if (!$desc) $score -= 20;
        elseif (!$descOk) $score -= 10;

        // Canonical
        $canonical = $post ? get_permalink($post) : get_home_url();
        $checks['canonical'] = [
            'value'  => $canonical,
            'status' => 'info',
            'note'   => 'WordPress auto-generates this',
        ];

        // H1 check (basic — just post title)
        $h1 = $post ? get_the_title($post) : null;
        $checks['h1'] = [
            'value'  => $h1,
            'status' => $h1 ? 'ok' : 'warn',
            'note'   => $h1 ? 'Found in post title' : 'No post title found',
        ];
        if (!$h1) $score -= 10;

        // OG tags — detect via registered wp_head actions
        $hasYoast     = defined('WPSEO_VERSION');
        $hasRankMath  = class_exists('\\RankMath');
        $hasHelixSeo  = false; // placeholder for future Helix SEO module
        $ogSource     = $hasYoast ? 'Yoast' : ($hasRankMath ? 'RankMath' : null);
        $checks['og_tags'] = [
            'status' => $ogSource ? 'ok' : 'warn',
            'note'   => $ogSource
                ? "Provided by {$ogSource}"
                : 'No OG plugin detected — install Yoast, RankMath, or add Helix SEO module',
        ];
        if (!$ogSource) $score -= 10;

        // Robots
        $isNoindex = is_singular() && $post && (get_post_meta($post->ID, '_yoast_wpseo_meta-robots-noindex', true) === '1');
        $checks['robots'] = [
            'status' => $isNoindex ? 'warn' : 'ok',
            'note'   => $isNoindex ? 'Page is set to noindex' : 'Indexable',
        ];
        if ($isNoindex) $score -= 15;

        return [
            'score'  => max(0, $score),
            'checks' => $checks,
        ];
    }
}
