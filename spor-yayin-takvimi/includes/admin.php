<?php

if (!defined('ABSPATH')) {
    exit;
}

function syt_admin_menu() {
    add_options_page(
        'Spor Yayın Takvimi',
        'Spor Yayın Takvimi',
        'manage_options',
        'syt-settings',
        'syt_admin_page'
    );
}
add_action('admin_menu', 'syt_admin_menu');

function syt_settings_init() {
    register_setting('syt_settings', 'syt_cache_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 3600,
    ));

    add_settings_section(
        'syt_settings_section',
        'Genel Ayarlar',
        '__return_false',
        'syt_settings'
    );

    add_settings_field(
        'syt_cache_duration',
        'Önbellek süresi (saniye)',
        'syt_cache_duration_field',
        'syt_settings',
        'syt_settings_section'
    );
}
add_action('admin_init', 'syt_settings_init');

function syt_cache_duration_field() {
    $value = (int) get_option('syt_cache_duration', 3600);
    echo '<input type="number" min="60" step="60" name="syt_cache_duration" value="' . esc_attr($value) . '" />';
}

function syt_handle_clear_cache() {
    if (!current_user_can('manage_options')) {
        wp_die('Yetkisiz işlem.');
    }

    check_admin_referer('syt_clear_cache');

    syt_clear_all_cache();

    $redirect = add_query_arg('syt_cache_cleared', '1', wp_get_referer());
    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_syt_clear_cache', 'syt_handle_clear_cache');

function syt_admin_page() {
    if (isset($_GET['syt_cache_cleared'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Önbellek temizlendi.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Spor Yayın Takvimi</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('syt_settings');
            do_settings_sections('syt_settings');
            submit_button();
            ?>
        </form>

        <hr />

        <h2>Önbellek</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('syt_clear_cache'); ?>
            <input type="hidden" name="action" value="syt_clear_cache" />
            <?php submit_button('Tüm önbelleği temizle', 'secondary'); ?>
        </form>

        <h2>Kullanim</h2>
        <p><code>[spor_yayin_takvimi]</code></p>
    </div>
    <?php
}
