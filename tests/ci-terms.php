<?php
/**
 * CI ONLY (plan 4.1b, companion 0.6.1): a post's categories and tags, pushed as OnCloud pushes them, through the
 * companion's own Oncloud_Posts::upsert() with the real WordPress and Polylang. One post in English and Arabic, a
 * category under a parent, two tags, and a category made here by hand with the same slug as OnCloud's parent: it is
 * linked, never renamed. Prints `ok …` lines; any failure stops with an error.
 *
 *   docker compose exec -T php vendor/bin/wp --path=web/wp eval-file - < tests/ci-terms.php
 */

defined('ABSPATH') || exit(1);

$fail = static function (string $what, $got = null): void {
    WP_CLI::error($what.($got === null ? '' : ': '.wp_json_encode($got, JSON_UNESCAPED_UNICODE)));
};
$by = static function (string $tax, string $id, string $lang): ?WP_Term {
    $ids = get_terms(['taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids', 'lang' => '', 'meta_key' => '_oncloud_term_id', 'meta_value' => $id]);
    foreach ((array) $ids as $t) {
        if (get_term_meta((int) $t, '_oncloud_lang', true) === $lang) {
            return get_term((int) $t, $tax);
        }
    }

    return null;
};

// The site's own category, made by hand, with the slug OnCloud's parent category will use.
$own = wp_insert_term('Company news', 'category', ['slug' => 'news']);
if (is_wp_error($own)) {
    $fail('hand-made category', $own->get_error_message());
}
$own = (int) $own['term_id'];
pll_set_term_language($own, 'en');

$terms = [
    'categories' => [
        ['oncloud_id' => '01CICATNEWS', 'name' => ['en' => 'News', 'ar' => 'أخبار'], 'slug' => ['en' => 'news', 'ar' => 'أخبار'], 'parent' => null, 'filed' => false],
        ['oncloud_id' => '01CICATCLOUD', 'name' => ['en' => 'Cloud', 'ar' => 'السحابة'], 'slug' => ['en' => 'cloud', 'ar' => 'السحابة'], 'parent' => '01CICATNEWS', 'filed' => true],
    ],
    'tags' => [
        ['oncloud_id' => '01CITAGAWS', 'name' => ['en' => 'AWS', 'ar' => 'أمازون'], 'slug' => ['en' => 'aws', 'ar' => 'أمازون']],
        ['oncloud_id' => '01CITAGOMAN', 'name' => ['en' => 'Oman', 'ar' => 'عُمان'], 'slug' => ['en' => 'oman', 'ar' => 'عمان']],
    ],
];
$push = static function (array $terms, string $version) use ($fail): array {
    $answer = Oncloud_Posts::upsert('01CITERMS0000000000000000T', [
        'oncloud_id' => '01CITERMS0000000000000000T', 'version' => $version, 'type' => 'post', 'status' => 'publish',
        'translations' => [
            'en' => ['title' => 'Our cloud year', 'content' => '<p>A year on.</p>', 'slug' => 'our-cloud-year'],
            'ar' => ['title' => 'عامنا في السحابة', 'content' => '<p>بعد عام.</p>', 'slug' => 'عامنا-في-السحابة'],
        ],
        'author' => null, 'featured_image' => null, 'terms' => $terms,
    ]);
    if (is_wp_error($answer)) {
        $fail('push', $answer->get_error_message());
    }
    if ((array) $answer['errors'] !== []) {
        $fail('push errors', $answer['errors']);
    }

    return array_map(static fn (array $p): int => $p['post_id'], $answer['posts']);
};

$posts = $push($terms, 'ci-1');

$news_en = $by('category', '01CICATNEWS', 'en');
$news_ar = $by('category', '01CICATNEWS', 'ar');
$cloud_en = $by('category', '01CICATCLOUD', 'en');
$cloud_ar = $by('category', '01CICATCLOUD', 'ar');
if (! $news_en || $news_en->term_id !== $own || $news_en->name !== 'Company news' || $news_en->slug !== 'news') {
    $fail('the hand-made category is linked, not renamed', $news_en);
}
echo "ok   hand-made 'news' linked to OnCloud's News, still 'Company news'\n";
if (! $news_ar || $news_ar->name !== 'أخبار' || ! $cloud_en || ! $cloud_ar || $cloud_ar->name !== 'السحابة') {
    $fail('categories made in both languages', [$news_ar, $cloud_en, $cloud_ar]);
}
if ($cloud_en->parent !== $own || $cloud_ar->parent !== $news_ar->term_id) {
    $fail('each Cloud under News in its own language', [$cloud_en->parent, $cloud_ar->parent]);
}
echo "ok   Cloud under News in English and Arabic\n";
if (pll_get_term_language($cloud_ar->term_id) !== 'ar' || pll_get_term_translations($cloud_en->term_id) != ['en' => $cloud_en->term_id, 'ar' => $cloud_ar->term_id]) {
    $fail('Cloud linked across languages', pll_get_term_translations($cloud_en->term_id));
}
echo "ok   Cloud linked across languages (Polylang)\n";

foreach (['en' => $cloud_en, 'ar' => $cloud_ar] as $lang => $cloud) {
    $cats = wp_get_post_terms($posts[$lang], 'category', ['fields' => 'ids']);
    $tags = wp_get_post_terms($posts[$lang], 'post_tag', ['fields' => 'names']);
    sort($tags);
    if ($cats !== [$cloud->term_id]) {
        $fail("{$lang} post filed under Cloud only", $cats);
    }
    $want = $lang === 'en' ? ['AWS', 'Oman'] : ['أمازون', 'عُمان'];
    sort($want);
    if ($tags !== $want) {
        $fail("{$lang} post tags", $tags);
    }
}
echo "ok   each post under Cloud with its two tags\n";

// OnCloud renames News: its Arabic term follows, the site's own English term does not.
$terms['categories'][0]['name'] = ['en' => 'Newsroom', 'ar' => 'غرفة الأخبار'];
$terms['categories'][0]['slug'] = ['en' => 'newsroom', 'ar' => 'غرفة-الأخبار'];
$push($terms, 'ci-2');
clean_term_cache([$own, $news_ar->term_id], 'category');
$after_en = get_term($own, 'category');
$after_ar = get_term($news_ar->term_id, 'category');
if ($after_en->name !== 'Company news' || $after_en->slug !== 'news' || $after_ar->name !== 'غرفة الأخبار') {
    $fail('a rename reaches only OnCloud\'s own term', [$after_en, $after_ar]);
}
echo "ok   rename: OnCloud's Arabic term renamed, the hand-made one untouched\n";
