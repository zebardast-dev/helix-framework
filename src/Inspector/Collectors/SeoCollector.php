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

        // ── Title ──────────────────────────────────────────────────
        // wp_get_document_title() can return HTML entities (e.g. &#8211;).
        // Decode before measuring so "Dev – Test" counts as 10, not 16.
        $rawTitle = wp_get_document_title();
        $title    = html_entity_decode($rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $titleLen = mb_strlen($title);
        $titleOk  = $titleLen >= 30 && $titleLen <= 60;

        $checks['title'] = [
            'value'  => $title,
            'length' => $titleLen,
            'status' => $titleOk ? 'ok' : 'warn',
            'note'   => $titleOk
                ? 'Good length (30–60 chars)'
                : ($titleLen < 30 ? 'Too short — aim for 30+ chars' : 'Too long — trim to 60 chars'),
        ];
        if (!$titleOk) $score -= 15;

        // ── Meta description ────────────────────────────────────────
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
            'length' => $descLen ?: null,
            'status' => $descOk ? 'ok' : ($desc ? 'warn' : 'error'),
            'note'   => !$desc
                ? 'Missing — add _meta_description post meta or use Yoast/RankMath'
                : ($descOk ? 'Good length (70–160 chars)' : ($descLen < 70 ? 'Too short — aim for 70+ chars' : 'Too long — trim to 160 chars')),
        ];
        if (!$desc)      $score -= 20;
        elseif (!$descOk) $score -= 10;

        // ── Canonical ───────────────────────────────────────────────
        $canonical = $post ? get_permalink($post) : get_home_url();
        $checks['canonical'] = [
            'value'  => $canonical,
            'status' => 'info',
            'note'   => 'WordPress auto-generates this',
        ];

        // ── Page title (H1 proxy) ───────────────────────────────────
        // We cannot scrape the rendered <h1> tag server-side without output buffering.
        // We read the post/page title as a reliable proxy and flag it clearly.
        $pageTitle = $post ? html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
        $checks['page_title'] = [
            'value'  => $pageTitle,
            'status' => $pageTitle ? 'ok' : 'warn',
            'note'   => $pageTitle
                ? 'Post/page title (verify your template outputs it as <h1>)'
                : 'No post/page title found',
        ];
        if (!$pageTitle) $score -= 10;

        // ── OG Tags ─────────────────────────────────────────────────
        $hasYoast    = defined('WPSEO_VERSION');
        $hasRankMath = class_exists('\\RankMath');
        $ogSource    = $hasYoast ? 'Yoast SEO' : ($hasRankMath ? 'RankMath' : null);

        $checks['og_tags'] = [
            'status' => $ogSource ? 'ok' : 'warn',
            'note'   => $ogSource
                ? "Provided by {$ogSource}"
                : 'No OG plugin detected — install Yoast, RankMath, or add Helix SEO module',
        ];
        if (!$ogSource) $score -= 10;

        // ── Robots ──────────────────────────────────────────────────
        $isNoindex = is_singular() && $post
            && get_post_meta($post->ID, '_yoast_wpseo_meta-robots-noindex', true) === '1';

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
