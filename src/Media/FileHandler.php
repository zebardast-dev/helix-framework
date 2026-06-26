<?php

namespace Helix\Media;

abstract class FileHandler
{
    /**
     * Return supported mime types: ['svg' => 'image/svg+xml']
     */
    abstract public function mimes(): array;

    /**
     * Called before upload — sanitize or reject the file.
     * Return $file unchanged to allow, or set $file['error'] to block.
     */
    public function sanitize(array $file): array
    {
        return $file;
    }

    /**
     * Output CSS/JS in admin_head for media library preview.
     */
    public function adminHead(): void {}

    public function extensions(): array
    {
        return array_keys($this->mimes());
    }

    protected function hasExtension(string $filename, string $ext): bool
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === $ext;
    }
}
