<?php
/**
 * The front door: WordPress core sits in web/wp (roots/wordpress-no-content), content in web/app.
 */

define('WP_USE_THEMES', true);
require __DIR__.'/wp/wp-blog-header.php';
