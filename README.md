# OnCloud WordPress

**What it is:** the distribution every OnCloud website and shop is built from (ADR 0052, `oncloud-documentations/platform/decisions/0052-oncloud-wordpress-distribution.md`). It contains:
- WordPress core and WooCommerce, **unmodified**, at pinned versions;
- the OnCloud must-use plugins;
- the allowed plugins;
- the `oncloud` parent theme.

**What it is not:** a fork. Nothing in core or WooCommerce is changed.

**Where the data lives:** OnCloud is the only source of truth. Any site can be wiped and rebuilt from OnCloud.

## Repositories

| Repo | Holds |
|---|---|
| `oncloud-wordpress` (this) | The distribution and its images |
| `oncloud-wordpress-plugin` | The companion plugin. Composer installs it here as a must-use plugin |
| `oncloud-vanilla` | OnCloud: the registry, provisioning and every record |

## Layout

| Path | What |
|---|---|
| `composer.json` | Core 7.1.3, WooCommerce 11.2.1, WP-CLI 2.12.0, the companion at a pinned commit. Exact pins only |
| `config/application.php` | Every setting from the environment, plus hardening |
| `config/allowlist.php` | The only plugins a site may run |
| `web/app/mu-plugins/` | `oncloud-loader.php` loads the lockdown and the companion (`oncloud/`, installed by Composer) |
| `web/app/themes/oncloud/` | The parent block theme: Inter and IBM Plex Sans Arabic, RTL-ready, `styles/` holds one look per business profile |
| `docker/` | The images: `php` (PHP-FPM 8.5) and `nginx` |
| `compose.yaml` | Local and CI: the two images, MariaDB 11.4, Valkey 8 |
| `bin/install.sh` | Installs a fresh site in a running stack |
| `bin/check.php` | The distribution's rules (pins, allowlist, hardening, theme, version) |
| `tests/run.php` | The lockdown's rules, without WordPress |

## Rules

1. **No fork, no hand edits.** A site's code changes only by a new image from this repository.
2. **Exact pins.** Every Composer requirement is an exact version or a commit (`bin/check.php`).
3. **Allowlist only.**
   - A plugin runs only if `config/allowlist.php` lists it.
   - Adding one is a pull request to that file and to `composer.json`.
4. **Locked outside `local`:**
   - no file editor;
   - no plugin, theme, core or language installs or updates;
   - no automatic updates;
   - no unfiltered HTML.
5. **Content lock (on outside `local`):**
   - Posts, pages, media, menus, templates and comments are not edited in WordPress. Content comes from OnCloud.
   - XML-RPC, application passwords, comments and self-registration are off.

## Run it locally

```sh
cp .env.example .env               # set the eight keys and salts
export COMPOSER_AUTH='{"github-oauth":{"github.com":"<token with read access to oncloud-wordpress-plugin>"}}'
docker compose build && docker compose up -d
docker compose exec php bin/install.sh "My site" me@example.com bakery
open http://localhost:8080
```

Check without Docker: `php bin/check.php && php tests/run.php`

## Releases and roll-out

**One release = one version** in `VERSION` plus `CHANGELOG.md`, and a tag `v<version>`.

**CI (`.github/workflows/build.yml`), in order:**
1. Check.
2. Build both images.
3. Start a clean Storefront site from them, with the lockdown on.
4. Assert the lockdown.
5. Publish `ghcr.io/waddah-alraisi/oncloud-wordpress-{php,nginx}`.

**Roll-out:** staging → canary → shared network → Private Store servers. The registry in OnCloud records each site's version (plan phases 2 and 7). Nobody updates a site by hand.

**Repository secret:** `PLUGIN_REPO_TOKEN`, a fine-grained token with read access to `oncloud-wordpress-plugin`.

## Status

- Phase 1 of `oncloud-documentations/planned/wordpress-platform.md`: version 0.1.0.
- `composer.lock` is written by the `lock` workflow (Actions → lock → Run workflow), then `build` runs on it.
- Dependabot opens weekly pull requests for WordPress, WooCommerce, WP-CLI, the base images and the actions.
