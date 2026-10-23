<?php
/**
 * WordPress reads this file. Every setting is in config/application.php, from the environment (ADR 0052).
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/config/application.php';

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__.'/wp/');
}

require_once ABSPATH.'wp-settings.php';
