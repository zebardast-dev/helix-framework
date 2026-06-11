<?php

namespace Helix\Support;

class Image
{
    protected string $src;
    protected int    $width;
    protected int    $height;
    protected string $alt;
    protected int    $id;

    public function __construct(string $src, int $width, int $height, string $alt = '', int $id = 0)
    {
        $this->src    = $src;
        $this->width  = $width;
        $this->height = $height;
        $this->alt    = $alt;
        $this->id     = $id;
    }

    public static function fromAttachment(int $id, string $size = 'full'): ?self
    {
        if (!$id) return null;

        $image = wp_get_attachment_image_src($id, $size);

        if (!$image) return null;

        [$src, $width, $height] = $image;

        $alt = get_post_meta($id, '_wp_attachment_image_alt', true);

        if (empty($alt)) {
            $alt = static::generateFallbackAlt($id);
        }

        return new self(
            src:    $src,
            width:  (int) $width,
            height: (int) $height,
            alt:    (string) $alt,
            id:     $id
        );
    }

    protected static function generateFallbackAlt(int $id): string
    {
        $attachment = get_post($id);

        if (!$attachment) return 'Image';

        if ($attachment->post_parent) {
            $title = get_the_title($attachment->post_parent);
            if (!empty($title)) return $title;
        }

        return !empty($attachment->post_title) ? $attachment->post_title : 'Image';
    }

    public function src(): string    { return $this->src; }
    public function width(): int     { return $this->width; }
    public function height(): int    { return $this->height; }
    public function alt(): string    { return $this->alt; }
    public function id(): int        { return $this->id; }

    public function toArray(): array
    {
        return [
            'src'    => $this->src,
            'width'  => $this->width,
            'height' => $this->height,
            'alt'    => $this->alt,
            'id'     => $this->id,
        ];
    }

    public function __toString(): string
    {
        return $this->src;
    }
}
