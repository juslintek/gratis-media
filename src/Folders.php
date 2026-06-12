<?php declare(strict_types=1);

namespace Gratis\Media;

defined('ABSPATH') || exit;

/**
 * Media folders: a hierarchical taxonomy on attachments plus a library filter.
 */
final class Folders {

    public const TAXONOMY = 'media_folder';

    /** Register the folder taxonomy and the media-library folder filter. */
    public static function register(): void {
        register_taxonomy(self::TAXONOMY, 'attachment', [
            'labels'            => [
                'name'          => __('Folders', 'gratis-media'),
                'singular_name' => __('Folder', 'gratis-media'),
            ],
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
        ]);

        add_action('restrict_manage_posts', [__CLASS__, 'renderFilter']);
    }

    /** Render the folder dropdown above the media list table. */
    public static function renderFilter(): void {
        global $typenow;
        if ('attachment' !== $typenow) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list-table filter value.
        $selected = isset($_GET['media_folder']) ? absint(wp_unslash($_GET['media_folder'])) : 0;
        wp_dropdown_categories([
            'taxonomy'        => self::TAXONOMY,
            'name'            => 'media_folder',
            'show_option_all' => __('All Folders', 'gratis-media'),
            'hierarchical'    => true,
            'hide_empty'      => false,
            'selected'        => $selected,
        ]);
    }

    /** Auto-assign an uploaded attachment to a type-based folder when enabled. */
    public static function maybeAutoAssign(int $attachmentId): void {
        if (!Settings::autoOrganize()) {
            return;
        }
        $mime   = (string) get_post_mime_type($attachmentId);
        $folder = self::folderForMime($mime);
        $term   = get_term_by('name', $folder, self::TAXONOMY);
        $term_id = $term ? (int) $term->term_id : self::create($folder);
        if ($term_id) {
            self::assign($attachmentId, $term_id);
        }
    }

    /** Map a MIME type to a default folder name. */
    public static function folderForMime(string $mime): string {
        return match (true) {
            str_starts_with($mime, 'image/') => __('Images', 'gratis-media'),
            str_starts_with($mime, 'video/') => __('Videos', 'gratis-media'),
            str_starts_with($mime, 'audio/') => __('Audio', 'gratis-media'),
            str_contains($mime, 'pdf')       => __('Documents', 'gratis-media'),
            default                          => __('Other', 'gratis-media'),
        };
    }

    /** Create a folder term, returning its ID (0 on failure). */
    public static function create(string $name, int $parent_id = 0): int {
        $term = wp_insert_term($name, self::TAXONOMY, ['parent' => $parent_id]);
        if (is_wp_error($term)) {
            $existing = get_term_by('name', $name, self::TAXONOMY);
            return $existing ? (int) $existing->term_id : 0;
        }
        return (int) $term['term_id'];
    }

    /** Assign an attachment to a folder. */
    public static function assign(int $attachmentId, int $folderId): void {
        wp_set_object_terms($attachmentId, [$folderId], self::TAXONOMY);
    }

    /**
     * All folder terms.
     *
     * @return array<int,\WP_Term>
     */
    public static function getAll(): array {
        $terms = get_terms(['taxonomy' => self::TAXONOMY, 'hide_empty' => false]);
        return is_array($terms) ? $terms : [];
    }

    /**
     * Organise existing unfiled attachments into type-based folders.
     *
     * @return int Number of attachments organised.
     */
    public static function organizeAll(): int {
        // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- one-off maintenance sweep.
        $attachments = get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => 'inherit']);
        $organized   = 0;
        foreach ($attachments as $att) {
            $folder  = self::folderForMime((string) $att->post_mime_type);
            $term    = get_term_by('name', $folder, self::TAXONOMY);
            $term_id = $term ? (int) $term->term_id : self::create($folder);
            if ($term_id) {
                self::assign($att->ID, $term_id);
                ++$organized;
            }
        }
        return $organized;
    }
}
