<?php

namespace Helix\Models;

use Helix\Support\Image;

class Product extends BasePost
{
    public function wc(): mixed
    {
        return function_exists('wc_get_product') ? wc_get_product($this->id()) : null;
    }

    public function price(): float
    {
        return (float) $this->meta('_price');
    }

    public function regularPrice(): float
    {
        return (float) $this->meta('_regular_price');
    }

    public function salePrice(): float
    {
        return (float) $this->meta('_sale_price');
    }

    public function isOnSale(): bool
    {
        return $this->salePrice() > 0 && $this->salePrice() < $this->regularPrice();
    }

    public function priceHtml(): string
    {
        return function_exists('wc_price') ? wc_price($this->price()) : (string) $this->price();
    }

    public function currency(): ?string
    {
        return function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : null;
    }

    public function isInStock(): bool
    {
        return $this->meta('_stock_status') === 'instock';
    }

    public function isOutOfStock(): bool
    {
        return $this->meta('_stock_status') === 'outofstock';
    }

    public function stockStatus(): ?string
    {
        return $this->meta('_stock_status');
    }

    public function stockQuantity(): ?int
    {
        $qty = $this->meta('_stock');
        return $qty !== '' ? (int) $qty : null;
    }

    public function sku(): ?string
    {
        return $this->meta('_sku');
    }

    public function type(): string
    {
        $terms = get_the_terms($this->id(), 'product_type');
        return (is_array($terms) && !empty($terms)) ? $terms[0]->slug : 'simple';
    }

    public function categories(): array
    {
        $terms = get_the_terms($this->id(), 'product_cat');
        return (!$terms || is_wp_error($terms)) ? [] : array_values($terms);
    }

    public function tags(): array
    {
        $terms = get_the_terms($this->id(), 'product_tag');
        return (!$terms || is_wp_error($terms)) ? [] : array_values($terms);
    }

    public function gallery(string $size = 'full', bool $includeFeatured = false): array
    {
        $images = [];

        if ($includeFeatured && has_post_thumbnail($this->id())) {
            $images[] = Image::fromAttachment(get_post_thumbnail_id($this->id()), $size);
        }

        $gallery = $this->meta('_product_image_gallery');

        if (!$gallery) {
            return array_filter($images);
        }

        foreach (explode(',', $gallery) as $id) {
            $img = Image::fromAttachment((int) $id, $size);
            if ($img) $images[] = $img;
        }

        return array_values($images);
    }

    public function isFeatured(): bool    { return $this->meta('_featured') === 'yes'; }
    public function isVirtual(): bool     { return $this->meta('_virtual') === 'yes'; }
    public function isDownloadable(): bool{ return $this->meta('_downloadable') === 'yes'; }
    public function rating(): float       { return (float) $this->meta('_average_rating'); }
    public function reviewCount(): int    { return (int) $this->meta('_wc_review_count'); }
}
