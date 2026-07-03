# Helix Framework

A config-driven WordPress framework for modern theme development.

Built on Laravel's Illuminate components — Blade templating, dependency injection, service architecture, console commands, and a debug inspector — without leaving the WordPress ecosystem.

## Requirements

- PHP 8.1+
- WordPress 5.9+
- Composer

## Installation

```bash
composer require helix/framework
```

## Usage

```php
// functions.php
require_once __DIR__ . '/vendor/autoload.php';

$app = Helix\Framework::create(__DIR__);
```

See [helix-starter](https://github.com/zebardast-dev/helix-starter) for a full working example.

## Documentation

Coming soon.

## Credits

HelixPress was developed through a human-AI collaborative workflow.

- **Architecture & Product Direction:** Mostafa Zebardast
- **Research & Technical Analysis:** ChatGPT & Claude
- **Implementation & Coding:** Mostafa Zebardast & Claude (Anthropic)
- **Review & Refinement:** AI-assisted

*This project reflects human-led design decisions, with AI utilized as a development partner for drafting, research, and implementation.*

## License

MIT
