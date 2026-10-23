<?php
/**
 * THE DISTRIBUTION'S LOCKDOWN (ADR 0052) — what no site on OnCloud WordPress may do, whoever is signed in, a super
 * admin included. The code only changes by a new image; the data only changes from OnCloud.
 *
 *   PLUGINS      only plugins in config/allowlist.php are ever active; `shop` ones only on Storefront and Private Store
 *   CAPABILITIES plugin, theme, core and language installs, updates, edits and switches; file editing; unfiltered HTML
 *                and uploads — refused for everyone. With the content lock (on outside `local`), so are writing posts,
 *                pages, media, menus and site-editor templates (edit_theme_options) and moderating comments: content comes from OnCloud (the companion writes it without
 *                a user, so no capability applies to it)
 *   DOORS        XML-RPC off; application passwords off (the companion signs its own requests); comments and pings
 *                closed; self-registration off; the generator tag removed
 *   HEADERS      nosniff, SAMEORIGIN, strict-origin-when-cross-origin, a closed Permissions-Policy (HSTS is nginx's)
 *
 * WooCommerce's own edit rights (products, orders) are the companion's to lock, with the shop work (plan phases 3, 6).
 */

defined('ABSPATH') || exit;

final class Oncloud_Lockdown
{
    /** Refused for everyone, always. */
    public const DENIED = [
        'install_plugins', 'upload_plugins', 'activate_plugins', 'deactivate_plugins', 'delete_plugins', 'edit_plugins', 'update_plugins',
        'install_themes', 'upload_themes', 'switch_themes', 'edit_themes', 'delete_themes', 'update_themes',
        'update_core', 'install_languages', 'update_languages', 'edit_files', 'unfiltered_html', 'unfiltered_upload',
        'manage_network_plugins', 'manage_network_themes', 'resume_plugins', 'resume_themes',
    ];

    /** Refused while content comes from OnCloud (the content lock). */
    public const CONTENT = [
        'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts',
        'edit_pages', 'edit_others_pages', 'edit_published_pages', 'publish_pages', 'delete_pages', 'delete_others_pages', 'delete_published_pages',
        'edit_post', 'delete_post', 'publish_post', 'edit_page', 'delete_page',
        'upload_files', 'moderate_comments', 'edit_comment', 'edit_theme_options',
    ];

    public static function register(): void
    {
        $lock = self::contentLocked();

        add_filter('option_active_plugins', static fn ($active): array => self::allowedPlugins((array) $active, ONCLOUD_ALLOWLIST, ONCLOUD_SHOP));
        add_filter('site_option_active_sitewide_plugins', static fn ($active): array => self::allowedSitewide((array) $active, ONCLOUD_ALLOWLIST, ONCLOUD_SHOP));
        add_filter('map_meta_cap', static fn (array $caps, string $cap): array => self::mapMetaCap($caps, $cap, $lock), 10, 2);

        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('wp_is_application_passwords_available', '__return_false');
        add_filter('pre_option_default_comment_status', static fn (): string => 'closed');
        add_filter('pre_option_default_ping_status', static fn (): string => 'closed');
        add_filter('comments_open', '__return_false');
        add_filter('pings_open', '__return_false');
        add_filter('pre_option_users_can_register', static fn (): string => '0');
        add_filter('pre_site_option_registration', static fn (): string => 'none');
        remove_action('wp_head', 'wp_generator');

        add_action('send_headers', static function (): void {
            foreach (self::headers() as $name => $value) {
                header($name.': '.$value);
            }
        });
    }

    public static function contentLocked(): bool
    {
        $setting = getenv('ONCLOUD_CONTENT_LOCK');
        if ($setting === '0' || $setting === '1') {
            return $setting === '1';
        }

        return (defined('ONCLOUD_ENV') ? ONCLOUD_ENV : 'production') !== 'local';
    }

    /**
     * The active plugins (`dir/file.php` list), keeping only the allowed ones.
     *
     * @param  list<string>  $active
     * @param  array<string, array{required: string}>  $allowlist
     * @return list<string>
     */
    public static function allowedPlugins(array $active, array $allowlist, bool $shop): array
    {
        return array_values(array_filter($active, static fn ($file): bool => is_string($file) && self::allowed($file, $allowlist, $shop)));
    }

    /**
     * The network-active plugins (`dir/file.php` => time), keeping only the allowed ones.
     *
     * @param  array<string, int>  $active
     * @param  array<string, array{required: string}>  $allowlist
     * @return array<string, int>
     */
    public static function allowedSitewide(array $active, array $allowlist, bool $shop): array
    {
        return array_filter($active, static fn ($file): bool => is_string($file) && self::allowed($file, $allowlist, $shop), ARRAY_FILTER_USE_KEY);
    }

    /** @param  array<string, array{required: string}>  $allowlist */
    public static function allowed(string $pluginFile, array $allowlist, bool $shop): bool
    {
        $slug = strtok($pluginFile, '/');
        $entry = $slug === false ? null : ($allowlist[$slug] ?? null);
        if ($entry === null) {
            return false;
        }

        return $entry['required'] !== 'shop' || $shop;
    }

    /**
     * @param  list<string>  $caps  the primitive capabilities `$cap` maps to
     * @return list<string>
     */
    public static function mapMetaCap(array $caps, string $cap, bool $contentLocked): array
    {
        $refused = in_array($cap, self::DENIED, true) || array_intersect($caps, self::DENIED) !== []
            || ($contentLocked && (in_array($cap, self::CONTENT, true) || array_intersect($caps, self::CONTENT) !== []));

        return $refused ? ['do_not_allow'] : $caps;
    }

    /** @return array<string, string> */
    public static function headers(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(self)',
        ];
    }
}
