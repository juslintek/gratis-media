<?php declare(strict_types=1);

namespace Gratis\Media;

defined('ABSPATH') || exit;

/**
 * `[gratis-gallery]` responsive image grid, optionally scoped to a folder.
 */
final class Gallery {

    /** Register the gallery shortcode and its styles. */
    public static function register(): void {
        add_shortcode('gratis-gallery', [__CLASS__, 'shortcode']);
        add_action('wp_enqueue_scripts', static function () {
            if (!is_admin()) {
                wp_register_style('gratis-gallery', false, [], GRATIS_MEDIA_VERSION);
                wp_add_inline_style('gratis-gallery', self::css());
                wp_enqueue_style('gratis-gallery');
            }
        });
    }

    /**
     * `[gratis-gallery folder="12" ids="1,2,3" columns="3" size="medium"]`.
     *
     * @param array<string,mixed>|string $atts Shortcode attributes.
     */
    public static function shortcode($atts): string {
        $atts = shortcode_atts([
            'folder'  => 0,
            'ids'     => '',
            'columns' => 3,
            'size'    => 'medium',
            'limit'   => 24,
        ], $atts);

        $columns = max(1, min(6, (int) $atts['columns']));
        $size    = sanitize_key((string) $atts['size']) ?: 'medium';
        $limit   = max(1, min(200, (int) $atts['limit']));

        $query = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- bounded by the limit attribute.
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ];

        $ids = array_filter(array_map('absint', explode(',', (string) $atts['ids'])));
        if (!empty($ids)) {
            $query['post__in'] = $ids;
            $query['orderby']  = 'post__in';
        } elseif ((int) $atts['folder'] > 0) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- intentional folder scoping.
            $query['tax_query'] = [
                [
                    'taxonomy' => Folders::TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => (int) $atts['folder'],
                ],
            ];
        }

        $images = get_posts($query);
        if (empty($images)) {
            return '';
        }

        $html = '<div class="gratis-gallery" style="display:grid;grid-template-columns:repeat('
            . $columns . ',1fr);gap:0.75rem">';
        foreach ($images as $image) {
            $img = wp_get_attachment_image($image->ID, $size, false, [
                'loading' => 'lazy',
                'style'   => 'width:100%;height:auto;border-radius:8px;display:block',
            ]);
            if ('' === $img) {
                continue;
            }
            $full = wp_get_attachment_image_url($image->ID, 'full');
            $html .= '<figure class="gratis-gallery-item" style="margin:0">';
            if ($full) {
                $html .= '<a href="' . esc_url($full) . '">' . $img . '</a>';
            } else {
                $html .= $img;
            }
            $html .= '</figure>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function css(): string {
        return '.gratis-gallery-item img{transition:transform .2s ease}.gratis-gallery-item img:hover{transform:scale(1.02)}@media(max-width:600px){.gratis-gallery{grid-template-columns:repeat(2,1fr)!important}}';
    }
}
