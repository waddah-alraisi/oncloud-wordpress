<?php
/**
 * CI ONLY (plan 4.4): an article and a case study in English and Arabic, written as OnCloud pushes them — through the
 * companion's own Oncloud_Posts::upsert(), with the real WordPress and Polylang — so the build can render every
 * template in both languages. Prints each page's path, one `name path` per line.
 *
 *   docker compose exec -T php vendor/bin/wp --path=web/wp eval-file - < tests/ci-content.php
 */

defined('ABSPATH') || exit(1);

$items = [
    'article' => ['oncloud_id' => '01CIARTICLE00000000000000A', 'type' => 'post', 'slugs' => ['en' => 'how-we-moved-to-the-cloud', 'ar' => 'كيف-انتقلنا-إلى-السحابة'],
        'titles' => ['en' => 'How we moved to the cloud', 'ar' => 'كيف انتقلنا إلى السحابة']],
    'study' => ['oncloud_id' => '01CISTUDY0000000000000000S', 'type' => 'case_study', 'slugs' => ['en' => 'al-bustan-logistics', 'ar' => 'البستان-للخدمات-اللوجستية'],
        'titles' => ['en' => 'Al Bustan Logistics', 'ar' => 'البستان للخدمات اللوجستية']],
];

foreach ($items as $name => $item) {
    $translations = [];
    foreach (['en', 'ar'] as $lang) {
        $translations[$lang] = [
            'title' => $item['titles'][$lang],
            'excerpt' => $lang === 'en' ? 'A year without downtime.' : 'عام بلا توقف.',
            'content' => $lang === 'en' ? '<p>Our first step.</p>' : '<p>خطوتنا الأولى.</p>',
            'slug' => $item['slugs'][$lang],
            'seo' => ['title' => $item['titles'][$lang].' — OnCloud CI', 'description' => $lang === 'en' ? 'What a year took.' : 'ما الذي تطلّبه عام.'],
        ];
    }

    $answer = Oncloud_Posts::upsert($item['oncloud_id'], [
        'oncloud_id' => $item['oncloud_id'], 'version' => 'ci', 'type' => $item['type'], 'status' => 'publish',
        'translations' => $translations, 'author' => ['oncloud_id' => '01CIPERSON', 'name' => ['en' => 'Sara Al Hinai', 'ar' => 'سارة الهنائية']],
        'featured_image' => null,
    ]);

    if (is_wp_error($answer)) {
        WP_CLI::error("{$name}: ".$answer->get_error_message());
    }

    foreach ($answer['posts'] as $lang => $post) {
        if ($post['status'] !== 'publish') {
            WP_CLI::error("{$name} {$lang}: {$post['status']}");
        }
        echo "{$name}-{$lang} ".wp_parse_url($post['url'], PHP_URL_PATH)."\n";
    }
}

if (function_exists('pll_get_post_translations')) {
    $en = array_values(Oncloud_Posts::find('01CIARTICLE00000000000000A'));
    $linked = pll_get_post_translations($en[0]);
    if (count($linked) !== 2) {
        WP_CLI::error('Polylang did not link the two languages: '.wp_json_encode($linked));
    }
}
