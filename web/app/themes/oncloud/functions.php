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
