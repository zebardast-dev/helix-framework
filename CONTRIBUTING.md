# helix-framework — Contribution Guide

## Overview

`helix/framework` is the core library consumed by `helix/starter` (and any other Helix-based theme).  
It must remain **theme-agnostic**: no hardcoded `get_template_directory()` calls, no starter-specific logic.

## Design Rules

| Rule | Reason |
|---|---|
| Use `$app->basePath()` / `PathResolver` for all paths | Stays portable across any theme root |
| Read runtime config via `config('key')` | Starter injects values; framework stays generic |
| Never require starter namespaces (`App\*`) directly | Discovered through config, not hardcoded |
| Keep `Framework::boot(array $config)` working | Backward compatibility with older starters |

## Entry Points

```
Framework::create(string $basePath)   ← recommended (loads .env + config automatically)
Framework::boot(array $config)        ← legacy (manual config array, kept for BC)
```

## Adding a New Service

1. Create your class under `src/` with namespace `Helix\`.
2. If it should be auto-bootable, document the config key it reads from (e.g. `config('app.services')`) in the class docblock.
3. Do **not** instantiate it unconditionally inside `Framework.php`; let the starter's `config/app.php` declare it.

## Running Locally with helix-starter

See [`helix-starter/CONTRIBUTING.md`](../helix-starter/CONTRIBUTING.md) for the Composer symlink setup.

```bash
# Dump autoload after adding/moving classes
composer dump-autoload
```

## Key Classes

| Class | Responsibility |
|---|---|
| `Foundation\Application` | DI container + basePath holder |
| `Foundation\PathResolver` | Typed path helpers derived from basePath |
| `Framework` | Static bootstrap orchestrator |
| `Config\Config` | Dot-notation config store |
| `Config\EnvLoader` | `.env` file parser |
| `View\ComponentDiscovery` | Blade component auto-registration |
| `View\ComposerDiscovery` | View composer auto-registration |
| `Template\Hierarchy` | WordPress template → Blade view resolver |
| `Console\Kernel` | CLI command dispatcher |
| `Inspector\Inspector` | Debug panel (gated by `inspector.enabled`) |
