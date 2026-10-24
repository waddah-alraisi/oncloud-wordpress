<?php
/**
 * The OnCloud parent theme. Everything visual is in theme.json and styles/{business profile}.json; the workspace's
 * brand (colours, fonts, logo) reaches the site's global styles from OnCloud through the companion (plan phase 4).
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', static function (): void {
    load_theme_textdomain('oncloud-theme', get_template_directory().'/languages');
    add_theme_support('woocommerce');
    add_theme_support('responsive-embeds');
    remove_theme_support('core-block-patterns');
});

/*
 * The site's business-profile look: styles/{profile}.json over theme.json, chosen by the option
 * `oncloud_style_variation` (set at install from the workspace's Business Profile).
 */
add_filter('wp_theme_json_data_theme', static function ($data) {
    $profile = get_option('oncloud_style_variation');
    if (! is_string($profile) || ! preg_match('/^[a-z_]+$/', $profile)) {
        return $data;
    }
    $file = get_template_directory().'/styles/'.$profile.'.json';
    $variation = is_file($file) ? wp_json_file_decode($file, ['associative' => true]) : null;
    if (! is_array($variation)) {
        return $data;
    }
    unset($variation['$schema'], $variation['title']);

    return $data->update_with($variation);
});

/*
 * TWO BLOCKS OF THE THEME'S OWN (plan 4.4), drawn on the server:
 *   oncloud/author     an OnCloud post's author, in the post's language (the companion keeps the name; no WordPress
 *                      user is made for a Pantry person); nothing when there is none
 *   oncloud/languages  the switch to the other languages of the page (Polylang), the current one left out
 *   oncloud/type-label the post type's name ("Case study"), in the page's language from the companion's labels
 */
add_action('init', static function (): void {
    register_block_type('oncloud/author', [
        'api_version' => 3,
        'uses_context' => ['postId'],
        'render_callback' => static function (array $attributes, string $content, $block): string {
            $postId = (int) ($block->context['postId'] ?? get_the_ID());
            $name = class_exists('Oncloud_Posts') && $postId > 0 ? Oncloud_Posts::author_name($postId) : '';

            return $name === '' ? '' : '<p class="wp-block-oncloud-author">'.esc_html($name).'</p>';
        },
    ]);

    register_block_type('oncloud/type-label', [
        'api_version' => 3,
        'uses_context' => ['postType'],
        'render_callback' => static function (array $attributes, string $content, $block): string {
            $type = get_post_type_object((string) ($block->context['postType'] ?? get_post_type()));

            return $type === null ? '' : '<p class="wp-block-oncloud-type-label">'.esc_html((string) $type->labels->singular_name).'</p>';
        },
    ]);

    register_block_type('oncloud/languages', [
        'api_version' => 3,
        'render_callback' => static function (): string {
            if (! function_exists('pll_the_languages')) {
                return '';
            }
            $links = (string) pll_the_languages(['echo' => 0, 'hide_current' => 1, 'hide_if_empty' => 0, 'display_names_as' => 'name']);

            return $links === '' ? '' : '<ul class="wp-block-oncloud-languages">'.$links.'</ul>';
        },
    ]);
});
