<?php declare(strict_types=1);

namespace Gratis\Media\CLI;

use Gratis\Media\Folders;

defined('ABSPATH') || exit;

/**
 * WP-CLI helpers for Gratis Media.
 */
final class MediaCommand {

    /**
     * Organize media into type-based folders.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Associative args (unused).
     */
    public function organize($args, $assoc_args): void {
        unset($args, $assoc_args);
        $organized = Folders::organizeAll();
        \WP_CLI::success("{$organized} files organized into folders.");
    }

    /**
     * Report potentially unused (unattached) media.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Show what would be reported without further output.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Associative args.
     */
    public function cleanup($args, $assoc_args): void {
        unset($args);
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off CLI maintenance report.
        $unused = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = 0 AND ID NOT IN (SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id')"
        );
        \WP_CLI::log(count($unused) . ' potentially unused media files.');
        if (isset($assoc_args['dry-run'])) {
            return;
        }
        \WP_CLI::log("Review in Media Library with the 'Unattached' filter.");
    }

    /**
     * List media folders.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Associative args (unused).
     */
    public function folders($args, $assoc_args): void {
        unset($args, $assoc_args);
        $terms = Folders::getAll();
        if (empty($terms)) {
            \WP_CLI::log('No folders.');
            return;
        }
        $rows = array_map(static fn($t) => [
            'ID'    => $t->term_id,
            'Name'  => $t->name,
            'Items' => $t->count,
        ], $terms);
        \WP_CLI\Utils\format_items('table', $rows, ['ID', 'Name', 'Items']);
    }
}
