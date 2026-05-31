<?php declare(strict_types=1);
namespace Gratis\Media;

final class Folders
{
    public static function register(): void
    {
        register_taxonomy('media_folder', 'attachment', [
            'labels'       => ['name' => 'Folders', 'singular_name' => 'Folder'],
            'hierarchical' => true,
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);

        // Add folder filter to media library
        add_action('restrict_manage_posts', function () {
            global $typenow;
            if ($typenow !== 'attachment') return;
            wp_dropdown_categories([
                'taxonomy'        => 'media_folder',
                'name'            => 'media_folder',
                'show_option_all' => 'All Folders',
                'hierarchical'    => true,
                'hide_empty'      => false,
                'selected'        => $_GET['media_folder'] ?? '',
            ]);
        });
    }

    public static function create(string $name, int $parent = 0): int
    {
        $term = wp_insert_term($name, 'media_folder', ['parent' => $parent]);
        return is_wp_error($term) ? 0 : $term['term_id'];
    }

    public static function assign(int $attachmentId, int $folderId): void
    {
        wp_set_object_terms($attachmentId, [$folderId], 'media_folder');
    }

    public static function list(): array
    {
        return get_terms(['taxonomy' => 'media_folder', 'hide_empty' => false]);
    }
}
