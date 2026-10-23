<?php
/**
 * The lockdown's rules, without WordPress: `php tests/run.php`. Each test is a closure; a failed check stops the run
 * with its name. CI runs it before building the image.
 */

define('ABSPATH', __DIR__.'/');
require dirname(__DIR__).'/web/app/mu-plugins/oncloud-distribution/lockdown.php';

$allowlist = [
    'woocommerce' => ['package' => 'wpackagist-plugin/woocommerce', 'required' => 'shop', 'why' => 'the shop'],
    'helper' => ['package' => 'wpackagist-plugin/helper', 'required' => 'always', 'why' => 'test'],
    'polylang' => ['package' => 'wpackagist-plugin/polylang', 'required' => 'always', 'why' => 'languages'],
];

$tests = [
    'a plugin not on the allowlist is never active' => fn () => Oncloud_Lockdown::allowedPlugins(['evil/evil.php', 'helper/helper.php'], $allowlist, true) === ['helper/helper.php'],
    'WooCommerce is active only on a shop plan' => fn () => Oncloud_Lockdown::allowedPlugins(['woocommerce/woocommerce.php'], $allowlist, false) === []
        && Oncloud_Lockdown::allowedPlugins(['woocommerce/woocommerce.php'], $allowlist, true) === ['woocommerce/woocommerce.php'],
    'a plugin named like an allowed one is not allowed' => fn () => ! Oncloud_Lockdown::allowed('woocommerce-evil/woocommerce.php', $allowlist, true),
    'network-active plugins are filtered by key' => fn () => Oncloud_Lockdown::allowedSitewide(['evil/evil.php' => 1, 'helper/helper.php' => 2], $allowlist, false) === ['helper/helper.php' => 2],
    'Polylang stays active on a site without a shop' => fn () => Oncloud_Lockdown::allowedPlugins(['polylang/polylang.php', 'woocommerce/woocommerce.php'], $allowlist, false) === ['polylang/polylang.php'],
    'non-strings in the stored list are dropped' => fn () => Oncloud_Lockdown::allowedPlugins([null, 7, 'helper/helper.php'], $allowlist, false) === ['helper/helper.php'],
    'installing plugins is refused' => fn () => Oncloud_Lockdown::mapMetaCap(['install_plugins'], 'install_plugins', false) === ['do_not_allow'],
    'a meta capability mapping to a refused one is refused' => fn () => Oncloud_Lockdown::mapMetaCap(['update_core'], 'update', false) === ['do_not_allow'],
    'unfiltered HTML is refused' => fn () => Oncloud_Lockdown::mapMetaCap(['unfiltered_html'], 'unfiltered_html', false) === ['do_not_allow'],
    'editing a post is refused under the content lock' => fn () => Oncloud_Lockdown::mapMetaCap(['edit_others_posts'], 'edit_post', true) === ['do_not_allow'],
    'editing a post is allowed without the content lock' => fn () => Oncloud_Lockdown::mapMetaCap(['edit_others_posts'], 'edit_post', false) === ['edit_others_posts'],
    'uploading media is refused under the content lock' => fn () => Oncloud_Lockdown::mapMetaCap(['upload_files'], 'upload_files', true) === ['do_not_allow'],
    'menus and templates are refused under the content lock' => fn () => Oncloud_Lockdown::mapMetaCap(['edit_theme_options'], 'edit_theme_options', true) === ['do_not_allow'],
    'settings stay with the site administrator' => fn () => Oncloud_Lockdown::mapMetaCap(['manage_options'], 'manage_options', true) === ['manage_options'],
    'reading is never refused' => fn () => Oncloud_Lockdown::mapMetaCap(['read'], 'read', true) === ['read'],
    'the content lock follows ONCLOUD_CONTENT_LOCK' => function () {
        putenv('ONCLOUD_CONTENT_LOCK=0');
        $off = Oncloud_Lockdown::contentLocked();
        putenv('ONCLOUD_CONTENT_LOCK=1');
        $on = Oncloud_Lockdown::contentLocked();
        putenv('ONCLOUD_CONTENT_LOCK');

        return $off === false && $on === true;
    },
    'without the setting the content lock is on outside local' => fn () => Oncloud_Lockdown::contentLocked() === true,
    'the headers close framing and sniffing' => fn () => Oncloud_Lockdown::headers()['X-Frame-Options'] === 'SAMEORIGIN' && Oncloud_Lockdown::headers()['X-Content-Type-Options'] === 'nosniff',
];

$failed = 0;
foreach ($tests as $name => $test) {
    $ok = (bool) $test();
    echo ($ok ? '  ok   ' : '  FAIL ').$name."\n";
    $failed += $ok ? 0 : 1;
}
echo count($tests).' tests, '.$failed." failed\n";
exit($failed === 0 ? 0 : 1);
