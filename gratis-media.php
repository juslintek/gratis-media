<?php
/**
 * Plugin Name: Gratis Media
 * Description: Advanced media library with folders + gallery block. Replaces FileBird ($39/yr) + Envira Gallery ($49/yr).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 * Text Domain: gratis-media
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Media\Folders::class, 'register']);
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-media', \Gratis\Media\CLI\MediaCommand::class);
    }
});
