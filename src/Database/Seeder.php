<?php

namespace Helix\Database;

abstract class Seeder
{
    abstract public function run(): void;

    protected function fake(?string $locale = null): \Faker\Generator
    {
        if (!class_exists(\Faker\Factory::class)) {
            throw new \RuntimeException(
                'Faker not installed. Run: composer require --dev fakerphp/faker'
            );
        }

        $locale ??= function_exists('config')
            ? config('app.faker_locale', 'en_US')
            : 'en_US';

        return \Faker\Factory::create($locale);
    }

    public function call(string ...$seeders): void
    {
        foreach ($seeders as $class) {
            (new $class())->run();
        }
    }

    protected function post(array $args): int
    {
        $defaults = ['post_status' => 'publish', 'post_author' => 1];
        $id = wp_insert_post(array_merge($defaults, $args), true);
        return is_wp_error($id) ? 0 : $id;
    }

    protected function term(string $name, string $taxonomy, array $args = []): int
    {
        $result = wp_insert_term($name, $taxonomy, $args);
        return is_wp_error($result) ? 0 : (int) $result['term_id'];
    }

    protected function option(string $key, mixed $value): void
    {
        update_option($key, $value);
    }

    protected function meta(int $postId, string $key, mixed $value): void
    {
        update_post_meta($postId, $key, $value);
    }
}
