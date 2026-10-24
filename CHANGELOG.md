# Changelog

Every release is one image (`ghcr.io/waddah-alraisi/oncloud-wordpress-{php,nginx}:<version>`), rolled out to every
site by the pipeline: build → staging → canary → shared network → Private Store servers (ADR 0052).

## 0.5.0 — 24 Oct 2026

Plan 4.4b: the live blocks.

- The companion 0.5.0 (`388f48e`): `oncloud/products`, `oncloud/events`, `oncloud/wins`, `oncloud/proof` — what
  OnCloud pushes of the workspace (only what it may show in public), drawn in the page's language.
- The theme lays them out as a card grid (`theme.json` styles).
- CI pushes the four blocks as OnCloud does and renders them on a page in English and Arabic.

## 0.4.0 — 24 Oct 2026

Plan 4.4 (part a): OnCloud's articles and case studies look right, in English and Arabic.

- `bin/install.sh` sets up Polylang's languages (`bin/polylang-languages.php`): English at the root, Arabic right to
  left under `/ar/`, case studies translatable.
- Theme templates: `home`, `single` (date and author), `single-case_study`, `archive`, `archive-case_study`; blocks of
  its own: `oncloud/author`, `oncloud/type-label`, `oncloud/languages` (the language switch, in the header).
- The companion 0.4.1 (`6c1f822`): case studies translated by Polylang, so an Arabic case study is under `/ar/`, right to left.
- CI publishes an article and a case study through the companion and renders every template in both languages: the
  author, the description, Open Graph, JSON-LD, `dir="rtl"` and the hreflang links.

## 0.3.1 — 24 Oct 2026

- The companion at `e158959`: the reconciliation counts case studies too (Lane B).

## 0.3.0 — 24 Oct 2026

- The companion 0.4.0 (`ae3639e`): articles and case studies from OnCloud (`PUT/DELETE /posts/{oncloud_id}`, plan 4.3),
  a post per language linked by Polylang, the `case_study` post type at `/case-studies/`, SEO and JSON-LD in the page
  head; and Lane B's provisioning routes (`/register`, `/heartbeat`, `/reconciliation`).
- nginx: the site's root goes to WordPress for every method. A `PUT` or `DELETE` to `/?rest_route=…` (OnCloud's calls on
  plain permalinks) was answered 405 by nginx's directory index.
- CI asserts the companion's version, the `case_study` post type and that `/posts` answers only signed requests.

## 0.2.0 — 23 Oct 2026

- Polylang on every site (allowlist `always`): English and Arabic per post and page. Its languages are set up from
  OnCloud by the companion (plan 4.3); `bin/install.sh` activates it.
- `lock` workflow: `add` brings in a newly allowed package and pins its newest stable version exactly.

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
