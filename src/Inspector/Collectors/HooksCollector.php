<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;

class HooksCollector implements CollectorInterface
{
    private array $fired = [];

    public function name(): string  { return 'hooks'; }
    public function title(): string { return 'Hooks'; }
    public function icon(): string  { return '🪝'; }

    public function boot(): void
    {
        add_action('all', function (): void {
            $hook = current_filter();
            if (!isset($this->fired[$hook])) {
                $this->fired[$hook] = 0;
            }
            $this->fired[$hook]++;
        });
    }

    public function collect(): array
    {
        global $wp_filter;

        $hooks = [];

        foreach ($this->fired as $name => $count) {
            $callbacks = 0;
            if (isset($wp_filter[$name]) && isset($wp_filter[$name]->callbacks)) {
                foreach ($wp_filter[$name]->callbacks as $priority) {
                    $callbacks += count($priority);
                }
            }

            $hooks[] = [
                'name'      => $name,
                'fired'     => $count,
                'callbacks' => $callbacks,
            ];
        }

        usort($hooks, fn($a, $b) => $b['fired'] <=> $a['fired']);

        return [
            'total_fired'  => array_sum($this->fired),
            'unique_hooks' => count($this->fired),
            'hooks'        => array_slice($hooks, 0, 100),
        ];
    }
}
