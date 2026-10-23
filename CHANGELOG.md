# Changelog

Every release is one image (`ghcr.io/waddah-alraisi/oncloud-wordpress-{php,nginx}:<version>`), rolled out to every
site by the pipeline: build → staging → canary → shared network → Private Store servers (ADR 0052).

## 0.1.0 — 23 Oct 2026

Phase 1 of `planned/wordpress-platform.md`: the distribution.

- WordPress 7.1.3, WooCommerce 11.2.1, WP-CLI 2.12.0, the companion `oncloud-wordpress-plugin` 0.3.3 as a must-use plugin.
- The plugin allowlist (`config/allowlist.php`): WooCommerce only, on Storefront and Private Store.
- Configuration from the environment only; file editing, plugin/theme installs and automatic updates off outside `local`.
- The lockdown must-use plugin: allowlist enforcement, refused capabilities (installs, updates, edits, unfiltered HTML;
  posts, pages, media and comments under the content lock), XML-RPC, application passwords, comments and
  self-registration off, security headers.
- The `oncloud` parent block theme: Inter and IBM Plex Sans Arabic (self-hosted, OFL), RTL-ready, one style variation per
  business profile (8).
- Two images (PHP-FPM, nginx), a local stack (MariaDB 11.4, Valkey 8), `bin/install.sh`, `bin/check.php`, CI.
