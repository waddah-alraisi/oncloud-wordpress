<?php
/**
 * Plugin Name: OnCloud distribution
 * Description: Loads the OnCloud companion and the distribution's lockdown. Must-use: a site cannot turn it off (ADR 0052).
 * Author:      OnCloud
 */

defined('ABSPATH') || exit;

require_once __DIR__.'/oncloud-distribution/lockdown.php';
Oncloud_Lockdown::register();

// The companion (oncloud-wordpress-plugin), installed here by Composer.
if (is_file(__DIR__.'/oncloud/oncloud.php')) {
    require_once __DIR__.'/oncloud/oncloud.php';
}
