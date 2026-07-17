# Helix Framework

`zebardast-dev/helix-framework`

A config-driven WordPress framework for modern theme development.

Built on Laravel's Illuminate components — Blade templating, dependency injection, service architecture, console commands, and a debug inspector — without leaving the WordPress ecosystem.

## Requirements

- PHP 8.1+
- WordPress 6.0+
- Composer

## Installation

```bash
composer require zebardast-dev/helix-framework
```

## Usage

```php
// functions.php
require_once __DIR__ . '/vendor/autoload.php';

$app = Helix\Framework::create(__DIR__);
```

See [helix-starter](https://github.com/zebardast-dev/helix-starter) for a full working theme example.

## Documentation

Welcome to the HelixPress documentation.

The full documentation is currently being prepared. In the meantime, you can use the official docs portal below to explore the available guides and topics.

**Official Documentation:**  
[https://helix.runflare.run/](https://helix.runflare.run/)

### Topics coming soon

- Getting Started
- Configuration
- Blade Templates
- Components
- View Composers
- Models and Queries
- Assets
- Custom Post Types
- CLI and Seeders
- Inspector
- WooCommerce
- Actions and Filters
- File Uploads

## Credits

HelixPress was developed through a human-AI collaborative workflow.

- **Architecture & Product Direction:** Mostafa Zebardast
- **Idea & Research:** Mostafa Zebardast ([@zebardast-dev](https://github.com/zebardast-dev)), Amirhossein Zebardast ([@amirz-dev](https://github.com/amirz-dev))
- **Implementation & Coding:** Mostafa Zebardast & Claude (Anthropic)
- **Review & Refinement:** AI-assisted

*This project reflects human-led design decisions, with AI utilized as a development partner for drafting, research, and implementation.*

## License

MIT
