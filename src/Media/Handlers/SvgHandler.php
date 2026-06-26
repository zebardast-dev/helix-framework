<?php

namespace Helix\Media\Handlers;

use DOMDocument;
use DOMElement;
use DOMNode;
use Helix\Media\FileHandler;

class SvgHandler extends FileHandler
{
    protected array $blockedTags = [
        'script', 'iframe', 'object', 'embed',
        'link', 'meta', 'foreignObject',
    ];

    public function mimes(): array
    {
        return ['svg' => 'image/svg+xml'];
    }

    public function sanitize(array $file): array
    {
        if (!$this->hasExtension($file['name'], 'svg')) {
            return $file;
        }

        $content   = file_get_contents($file['tmp_name']);
        $sanitized = $this->sanitizeSvg($content);

        if ($sanitized === false) {
            $file['error'] = __('Invalid or unsafe SVG file.');
            return $file;
        }

        file_put_contents($file['tmp_name'], $sanitized);
        return $file;
    }

    public function adminHead(): void
    {
        echo '<style>
            td.media-icon img[src$=".svg"],
            img.attachment-thumbnail[src$=".svg"],
            .thumbnail img[src$=".svg"] {
                width: 100% !important;
                height: auto !important;
            }
        </style>';
    }

    protected function sanitizeSvg(string $content): string|false
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        if (!$dom->loadXML($content, LIBXML_NONET | LIBXML_NOENT)) {
            return false;
        }

        if (!$dom->documentElement || strtolower($dom->documentElement->tagName) !== 'svg') {
            return false;
        }

        $this->cleanNode($dom->documentElement);

        return $dom->saveXML($dom->documentElement);
    }

    protected function cleanNode(DOMNode $node): void
    {
        $toRemove = [];

        foreach ($node->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }

            if (in_array(strtolower($child->localName), $this->blockedTags, true)) {
                $toRemove[] = $child;
                continue;
            }

            $this->cleanAttributes($child);
            $this->cleanNode($child);
        }

        foreach ($toRemove as $el) {
            $node->removeChild($el);
        }
    }

    protected function cleanAttributes(DOMElement $el): void
    {
        $toRemove = [];

        foreach ($el->attributes as $attr) {
            $name  = strtolower($attr->name);
            $value = strtolower(preg_replace('/\s+/', '', $attr->value));

            if (str_starts_with($name, 'on')) {
                $toRemove[] = $attr->name;
                continue;
            }

            if (in_array($name, ['href', 'xlink:href', 'src', 'action'], true)) {
                if (str_starts_with($value, 'javascript:')) {
                    $toRemove[] = $attr->name;
                }
            }
        }

        foreach ($toRemove as $name) {
            $el->removeAttribute($name);
        }
    }
}
