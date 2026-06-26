<?php

namespace Helix\Media;

class FileUploader
{
    protected array $handlers = [];

    public function allow(FileHandler ...$handlers): static
    {
        foreach ($handlers as $handler) {
            $this->handlers[] = $handler;
        }
        return $this;
    }

    public function register(): void
    {
        if (empty($this->handlers)) {
            return;
        }

        add_filter('upload_mimes', function (array $mimes): array {
            foreach ($this->handlers as $handler) {
                $mimes = array_merge($mimes, $handler->mimes());
            }
            return $mimes;
        });

        // WordPress 4.7.1+ verifies mime types against file content —
        // we short-circuit for our registered extensions so WP doesn't reject them.
        add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
            foreach ($this->handlers as $handler) {
                foreach ($handler->extensions() as $ext) {
                    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === $ext) {
                        $data['ext']  = $ext;
                        $data['type'] = array_values($handler->mimes())[0];
                        return $data;
                    }
                }
            }
            return $data;
        }, 10, 4);

        add_filter('wp_handle_upload_prefilter', function (array $file): array {
            foreach ($this->handlers as $handler) {
                foreach ($handler->extensions() as $ext) {
                    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === $ext) {
                        return $handler->sanitize($file);
                    }
                }
            }
            return $file;
        });

        add_action('admin_head', function (): void {
            foreach ($this->handlers as $handler) {
                $handler->adminHead();
            }
        });
    }
}
