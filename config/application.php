<?php
/**
 * AN ONCLOUD SITE'S CONFIGURATION (ADR 0052) — every value from the environment, never from a file in the image, so
 * one image serves every site and a rebuild needs only the environment and OnCloud's data. On a Forge server the
 * environment is the site's .env (config/dotenv.php); real environment variables win over it.
 *
 *   ONCLOUD_ENV          production | staging | local                     (default production)
 *   ONCLOUD_PLAN         site | storefront | private_store                (OnCloud Site, Storefront, Private Store)
 *   ONCLOUD_NETWORK      1 on the shared network (multisite), 0 on a Private Store server
 *   ONCLOUD_NETWORK_READY 1 once the network is installed (sets MULTISITE and its constants)
 *   WP_HOME              https://the-site — a Private Store only; on the network each site's address is in its row
 *   DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PREFIX (default wp_)
 *   AUTH_KEY … NONCE_SALT the eight WordPress keys and salts
 *   WP_REDIS_HOST        the object cache, when set
 *   DOMAIN_CURRENT_SITE  the network's main host (multisite only)
 *
 * HARDENING, always on outside `local`: no file editor, no plugin or theme installs or updates from the dashboard, no
 * automatic updates — the code changes only by a new image from this repository.
 */

// On a Forge server the settings are in the site's .env; real environment variables (Docker, CI) win. @see dotenv.php
require_once __DIR__.'/dotenv.php';
oncloud_load_dotenv(dirname(__DIR__).'/.env');

$env = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
};

$required = static function (string $key) use ($env): string {
    $value = $env($key);
    if ($value === null) {
        throw new RuntimeException("OnCloud WordPress: the environment variable {$key} is not set.");
    }

    return $value;
};

define('ONCLOUD_ENV', $env('ONCLOUD_ENV', 'production'));
define('ONCLOUD_PLAN', in_array($env('ONCLOUD_PLAN'), ['site', 'storefront', 'private_store'], true) ? $env('ONCLOUD_PLAN') : 'site');
define('ONCLOUD_SHOP', ONCLOUD_PLAN !== 'site');
define('ONCLOUD_ROOT', dirname(__DIR__));
define('ONCLOUD_ALLOWLIST', require __DIR__.'/allowlist.php');

// The shared network (OnCloud Site and Storefront) is one install with a site per tenant, each on its own domain; a
// Private Store server is a single site.
define('ONCLOUD_NETWORK', $env('ONCLOUD_NETWORK', ONCLOUD_PLAN === 'private_store' ? '0' : '1') === '1');

// Behind the load balancer / Cloudflare: trust the forwarded scheme.
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Paths: core in web/wp, content in web/app. On the network each site's address lives in its own row, so WP_HOME is
// set only on a single site, and content URLs follow the host that was asked.
define('WP_CONTENT_DIR', ONCLOUD_ROOT.'/web/app');
if (ONCLOUD_NETWORK) {
    $scheme = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
    $host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? $required('DOMAIN_CURRENT_SITE')));
    define('WP_CONTENT_URL', $scheme.'://'.$host.'/app');
} else {
    define('WP_HOME', rtrim($required('WP_HOME'), '/'));
    define('WP_SITEURL', WP_HOME.'/wp');
    define('WP_CONTENT_URL', WP_HOME.'/app');
}

// Database.
define('DB_HOST', $required('DB_HOST'));
define('DB_NAME', $required('DB_NAME'));
define('DB_USER', $required('DB_USER'));
define('DB_PASSWORD', $required('DB_PASSWORD'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
$GLOBALS['table_prefix'] = $env('DB_PREFIX', 'wp_');

// Keys and salts.
foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $key) {
    define($key, $required($key));
}

// Hardening.
$locked = ONCLOUD_ENV !== 'local';
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', $locked);
define('AUTOMATIC_UPDATER_DISABLED', true);
define('WP_AUTO_UPDATE_CORE', false);
define('WP_ENVIRONMENT_TYPE', ONCLOUD_ENV === 'local' ? 'local' : (ONCLOUD_ENV === 'staging' ? 'staging' : 'production'));
define('FORCE_SSL_ADMIN', ONCLOUD_ENV !== 'local');
define('WP_POST_REVISIONS', 5);
define('WP_DEBUG', ONCLOUD_ENV === 'local');
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', ONCLOUD_ENV === 'local' ? true : 'php://stderr');
define('SCRIPT_DEBUG', false);
define('DISABLE_WP_CRON', $env('DISABLE_WP_CRON', $locked ? '1' : '0') === '1');

// Object cache (Redis), when configured.
if ($env('WP_REDIS_HOST') !== null) {
    define('WP_REDIS_HOST', $env('WP_REDIS_HOST'));
    define('WP_REDIS_PORT', (int) $env('WP_REDIS_PORT', '6379'));
    define('WP_CACHE_KEY_SALT', $env('WP_CACHE_KEY_SALT', DB_NAME.':'));
}

// The network's constants. Before the network is installed (`bin/install.sh` runs `wp core multisite-install`), only
// WP_ALLOW_MULTISITE is set: ONCLOUD_NETWORK_READY=1 turns the rest on.
if (ONCLOUD_NETWORK) {
    define('WP_ALLOW_MULTISITE', true);
}
if (ONCLOUD_NETWORK && $env('ONCLOUD_NETWORK_READY') === '1') {
    define('MULTISITE', true);
    define('SUBDOMAIN_INSTALL', true);
    define('DOMAIN_CURRENT_SITE', $required('DOMAIN_CURRENT_SITE'));
    define('PATH_CURRENT_SITE', '/');
    define('SITE_ID_CURRENT_SITE', 1);
    define('BLOG_ID_CURRENT_SITE', 1);
    define('COOKIE_DOMAIN', '');
}
