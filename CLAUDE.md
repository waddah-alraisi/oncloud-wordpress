# CLAUDE.md — oncloud-wordpress

## Git

Only `main` (agreed 22 Oct 2026). Pull `main` before starting. A session branch is merged into `main` the same day and deleted. Never start from a leftover `claude/…` branch.

## What this repo is

The OnCloud WordPress distribution (ADR 0052). WordPress core and WooCommerce are never modified: no fork.

Every change keeps these passing:
- `php bin/check.php`
- `php tests/run.php`

## Documentation

Documentation does not live in this repo. It is in `~/Desktop/Sites/oncloud-documentations/`:
- the plan: `planned/wordpress-platform.md`;
- the decision: `platform/decisions/0052-oncloud-wordpress-distribution.md`;
- reports for this repo: `oncloud-wordpress/reports/`.
