<?php

/**
 *
 * The file responsible for starting the MOJ Markdown plugin
 *
 * @package php-markdown-extra
 *
 * Plugin name: Markdown Extra (Justice Digital)
 * Plugin URI:  https://github.com/ministryofjustice/php-markdown-extra
 * Description: Converts markdown strings to HTML, allowing editors to write rich content with ease
 * Version:     2.0.0
 * Author:      Ministry of Justice
 * Text domain: php-markdown-extra
 * License:     MIT License
 * License URI: https://opensource.org/licenses/MIT
 * Copyright:   Crown Copyright (c) Ministry of Justice
 **/


// Do not allow access outside WP
defined('ABSPATH') || exit;

/**
 * Uses Michelf namespace
 * Bootstrap is maintained by Justice Digital
 */
use Michelf\Bootstrap;

new Bootstrap();
