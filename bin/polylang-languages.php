<?php
/**
 * THE SITE'S LANGUAGES (plan 4.4) — run by bin/install.sh: `wp eval-file bin/polylang-languages.php`.
 *
 * English (the default, at the site's root) and Arabic (right to left, under /ar/), and OnCloud's case studies
 * translatable like posts. OnCloud sends both languages of every item; the companion writes a post per language and
 * Polylang links them (ADR 0052, decided 23 Oct 2026). Safe to run again: a language that exists is kept.
 */

defined('ABSPATH') || exit(1);

if (! function_exists('PLL') || ! function_exists('pll_languages_list')) {
    WP_CLI::error('Polylang is not active: activate it first.');
}

$languages = [
    ['name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => false, 'term_group' => 0, 'flag' => 'gb'],
    ['name' => 'العربية', 'slug' => 'ar', 'locale' => 'ar', 'rtl' => true, 'term_group' => 1, 'flag' => 'om'],
];

$model = PLL()->model;

foreach ($languages as $language) {
    if (in_array($language['slug'], (array) pll_languages_list(['fields' => 'slug']), true)) {
        WP_CLI::log("Language {$language['slug']}: already set up.");

        continue;
    }

    // Polylang 3.7 moved languages to `$model->languages`; before it, the admin model added them.
    if (isset($model->languages) && is_object($model->languages) && method_exists($model->languages, 'add')) {
        $result = $model->languages->add($language);
    } elseif (method_exists($model, 'add_language')) {
        $result = $model->add_language($language);
    } else {
        WP_CLI::error('This Polylang version has no way to add a language that this script knows.');
    }

    if (is_wp_error($result)) {
        WP_CLI::error("Language {$language['slug']}: ".$result->get_error_message());
    }

    if (method_exists($model, 'clean_languages_cache')) {
        $model->clean_languages_cache();
    }

    WP_CLI::log("Language {$language['slug']}: added.");
}

// English at the root, every other language under its own directory; case studies translatable.
$options = (array) get_option('polylang', []);
$options['default_lang'] = 'en';
$options['force_lang'] = 1;
$options['hide_default'] = 1;
$options['rewrite'] = 1;
$options['post_types'] = array_values(array_unique(array_merge((array) ($options['post_types'] ?? []), ['case_study'])));
update_option('polylang', $options);

WP_CLI::success('Languages: '.implode(', ', (array) pll_languages_list(['fields' => 'slug'])));
