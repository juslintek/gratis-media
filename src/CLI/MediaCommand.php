<?php declare(strict_types=1);
namespace Gratis\Media\CLI;

use Gratis\Media\Folders;

final class MediaCommand
{
    /**
     * Organize media into folders.
     * @when after_wp_load
     */
    public function organize($args, $assoc_args): void
    {
        $attachments = get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => 'inherit']);
        $organized = 0;
        foreach ($attachments as $att) {
            $mime = $att->post_mime_type;
            $folder = match (true) {
                str_starts_with($mime, 'image/') => 'Images',
                str_starts_with($mime, 'video/') => 'Videos',
                str_starts_with($mime, 'audio/') => 'Audio',
                str_contains($mime, 'pdf')       => 'Documents',
                default                          => 'Other',
            };
            $term = get_term_by('name', $folder, 'media_folder');
            if (!$term) {
                $result = wp_insert_term($folder, 'media_folder');
                $term_id = is_wp_error($result) ? 0 : $result['term_id'];
            } else {
                $term_id = $term->term_id;
            }
            if ($term_id) { Folders::assign($att->ID, $term_id); $organized++; }
        }
        \WP_CLI::success("{$organized} files organized into folders.");
    }

    /**
     * Clean up unused media.
     * [--dry-run] : Show what would be deleted.
     * @when after_wp_load
     */
    public function cleanup($args, $assoc_args): void
    {
        global $wpdb;
        $unused = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = 0 AND ID NOT IN (SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id')");
        \WP_CLI::log(count($unused) . " potentially unused media files.");
        if (isset($assoc_args['dry-run'])) return;
        // Don't auto-delete — just report
        \WP_CLI::log("Review in Media Library with 'Unattached' filter.");
    }
}
