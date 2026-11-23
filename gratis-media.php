<?php
/**
 * Plugin Name: Gratis Media
 * Plugin URI: https://github.com/juslintek/gratis-media
 * Description: Advanced media library with folders + gallery shortcode.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-media
 *
 * @package Gratis\Media
 */

defined('ABSPATH') || exit;

define('GRATIS_MEDIA_VERSION', '1.0.0');
define('GRATIS_MEDIA_FILE', __FILE__);
define('GRATIS_MEDIA_DIR', plugin_dir_path(__FILE__));
define('GRATIS_MEDIA_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Media\Folders::class, 'register']);
add_action('init', [\Gratis\Media\Gallery::class, 'register']);
add_action('add_attachment', [\Gratis\Media\Folders::class, 'maybeAutoAssign']);

add_action('admin_menu', [\Gratis\Media\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Media\Settings::class, 'register_settings']);
\Gratis\Media\Settings::register_actions();

add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-media', \Gratis\Media\CLI\MediaCommand::class);
    }
});
