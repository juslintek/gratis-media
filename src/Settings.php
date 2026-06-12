<?php declare(strict_types=1);

namespace Gratis\Media;

defined('ABSPATH') || exit;

/**
 * Settings + a "organise now" maintenance action for Gratis Media.
 */
final class Settings {

    private const GROUP = 'gratis_media_settings_group';
    private const PAGE  = 'gratis-media-settings';

    private const OPT_AUTO = 'gratis_media_auto_organize';

    private const ACTION_ORGANIZE = 'gratis_media_organize';

    /** Register the settings page under the Media menu. */
    public static function register_menu(): void {
        add_submenu_page(
            'upload.php',
            __('Media Settings', 'gratis-media'),
            __('Folders & Gallery', 'gratis-media'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /** Register options. */
    public static function register_settings(): void {
        register_setting(self::GROUP, self::OPT_AUTO, [
            'type'              => 'boolean',
            'sanitize_callback' => static fn($v) => (bool) $v,
            'default'           => false,
        ]);
    }

    /** Register the admin-post side-effecting actions. */
    public static function register_actions(): void {
        add_action('admin_post_' . self::ACTION_ORGANIZE, [__CLASS__, 'handleOrganize']);
    }

    /** Whether uploads are auto-assigned to a type-based folder. */
    public static function autoOrganize(): bool {
        return (bool) get_option(self::OPT_AUTO, false);
    }

    /** Handle the "organise existing media" action. */
    public static function handleOrganize(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'gratis-media'));
        }
        check_admin_referer(self::ACTION_ORGANIZE);
        $count = Folders::organizeAll();
        wp_safe_redirect(add_query_arg(
            ['page' => self::PAGE, 'gratis_media_msg' => $count],
            admin_url('upload.php')
        ));
        exit;
    }

    /** Render the settings screen. */
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-media'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Media Folders & Gallery', 'gratis-media') . '</h1>';

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display flag after a nonce-checked redirect.
        if (isset($_GET['gratis_media_msg'])) {
            $count = absint(wp_unslash($_GET['gratis_media_msg']));
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html(sprintf(
                    /* translators: %d: number of files organised */
                    _n('%d file organised into folders.', '%d files organised into folders.', $count, 'gratis-media'),
                    $count
                )) . '</p></div>';
        }

        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row">' . esc_html__('Auto-organise uploads', 'gratis-media') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPT_AUTO) . '" value="1"' . checked(self::autoOrganize(), true, false) . '> ';
        echo esc_html__('Automatically file new uploads into Images / Videos / Audio / Documents folders.', 'gratis-media') . '</label></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Save settings', 'gratis-media'));
        echo '</form>';

        echo '<hr><h2>' . esc_html__('Organise existing media', 'gratis-media') . '</h2>';
        echo '<p>' . esc_html__('Sort all existing attachments into type-based folders now.', 'gratis-media') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION_ORGANIZE) . '">';
        wp_nonce_field(self::ACTION_ORGANIZE);
        submit_button(__('Organise now', 'gratis-media'), 'secondary');
        echo '</form>';

        echo '<hr><h2>' . esc_html__('Gallery shortcode', 'gratis-media') . '</h2>';
        echo '<p><code>[gratis-gallery folder="12" columns="3" size="medium"]</code></p>';
        echo '<p><code>[gratis-gallery ids="1,2,3" columns="4"]</code></p>';
        echo '</div>';
    }
}
