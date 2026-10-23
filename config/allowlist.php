<?php
/**
 * THE PLUGINS AN ONCLOUD SITE MAY RUN (ADR 0052). Anything not listed here is never active, whatever the database says:
 * the must-use plugin `oncloud-distribution/lockdown.php` filters the active list against this file, and
 * `bin/check.php` refuses a build whose `web/app/plugins/` holds a plugin not listed, or lists one not installed.
 *
 *   slug => [
 *     'package'   the Composer package that installs it, at the exact version in composer.json
 *     'required'  'always' | 'shop' (only on Storefront and Private Store) | 'never' (installed, off unless chosen)
 *     'why'       one line: why OnCloud allows it
 *   ]
 *
 * The OnCloud companion is not here: it is a must-use plugin (web/app/mu-plugins/oncloud/), always on, never listed.
 * Adding a plugin is a pull request to this file and composer.json together.
 */

return [
    'woocommerce' => [
        'package' => 'wpackagist-plugin/woocommerce',
        'required' => 'shop',
        'why' => 'The shop. Catalogue, prices, stock and orders are OnCloud\'s; WooCommerce shows and sells them.',
    ],
];
