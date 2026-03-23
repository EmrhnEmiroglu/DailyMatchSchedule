<?php

if (!defined('ABSPATH')) {
    exit;
}

function syt_admin_menu() {
    add_options_page(
        'Sporkulis Yayın Takvimi',
        'Sporkulis Yayın Takvimi',
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

    register_setting('syt_settings', 'syt_default_sport', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    register_setting('syt_settings', 'syt_categories', array(
        'type' => 'string',
        'sanitize_callback' => 'syt_sanitize_categories',
        'default' => '',
    ));

    register_setting('syt_settings', 'syt_background_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#f4f6f9',
    ));
}
add_action('admin_init', 'syt_settings_init');

function syt_admin_enqueue($hook) {
    if ($hook !== 'settings_page_syt-settings') {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('syt-admin', SYT_URL . 'assets/css/admin.css', array(), SYT_VERSION);
    wp_enqueue_script('syt-admin', SYT_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), SYT_VERSION, true);
}
add_action('admin_enqueue_scripts', 'syt_admin_enqueue');

function syt_sanitize_categories($value) {
    if (!is_string($value)) {
        return '';
    }

    $value = wp_unslash($value);
    $parts = preg_split('/

|
|
|,|;/', $value);
    $clean = array();

    foreach ($parts as $part) {
        $label = sanitize_text_field(trim($part));
        if ($label !== '') {
            $clean[] = $label;
        }
    }

    if (empty($clean)) {
        $clean = syt_default_categories();
    }

    return implode("
", $clean);
}

function syt_cache_duration_field() {
    $value = (int) get_option('syt_cache_duration', 3600);
    echo '<input id="syt_cache_duration" type="number" min="60" step="60" name="syt_cache_duration" value="' . esc_attr($value) . '" />';
}

function syt_default_sport_field() {
    $value = get_option('syt_default_sport', '');
    echo '<input id="syt_default_sport" type="text" name="syt_default_sport" value="' . esc_attr($value) . '" placeholder="Örn: Futbol" />';
}

function syt_categories_field() {
    $value = get_option('syt_categories', '');
    if ($value === '') {
        $value = implode("
", syt_default_categories());
    }

    echo '<textarea id="syt_categories" name="syt_categories" rows="6" class="large-text code">' . esc_textarea($value) . '</textarea>';
}

function syt_background_color_field() {
    $value = get_option('syt_background_color', '#f4f6f9');
    $value = $value ? $value : '#f4f6f9';
    echo '<input id="syt_background_color" type="text" class="syt-color-field" data-preview="#syt-bg-preview" name="syt_background_color" value="' . esc_attr($value) . '" />';
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
    <div class="wrap syt-admin-wrap">
        <div class="syt-admin-hero">
            <div>
                <h1>Sporkulis Yayın Takvimi</h1>
                <p>Yayın verisini yönetmek ve görüntüyü özelleştirmek için aşağıdaki ayarları kullanın.</p>
            </div>
            <span class="syt-admin-tag">v<?php echo esc_html(SYT_VERSION); ?></span>
        </div>

        <div class="syt-admin-grid">
            <div class="syt-admin-main">
                <form method="post" action="options.php">
                    <?php settings_fields('syt_settings'); ?>

                    <div class="syt-admin-card">
                        <h2>Genel Ayarlar</h2>

                        <div class="syt-admin-field">
                            <label for="syt_cache_duration">Önbellek süresi (saniye)</label>
                            <?php syt_cache_duration_field(); ?>
                            <p class="syt-admin-hint">Veri kaynağına yapılan istekleri azaltır.</p>
                        </div>

                        <div class="syt-admin-field">
                            <label for="syt_default_sport">Varsayılan spor filtresi</label>
                            <?php syt_default_sport_field(); ?>
                            <p class="syt-admin-hint">Boş bırakılırsa varsayılan filtre "Tümü" olur.</p>
                        </div>

                        <div class="syt-admin-actions">
                            <?php submit_button('Ayarları Kaydet', 'primary', 'submit', false); ?>
                        </div>
                    </div>

                    <div class="syt-admin-card">
                        <h2>Görünüm</h2>

                        <div class="syt-admin-field">
                            <label for="syt_background_color">Arka plan rengi</label>
                            <?php syt_background_color_field(); ?>
                            <div id="syt-bg-preview" class="syt-color-preview"></div>
                            <p class="syt-admin-hint">Eklenti kutusunun arka plan rengi değiştirilebilir.</p>
                        </div>

                        <p class="syt-admin-hint">Ana tema rengi sabittir: #ef7123</p>
                    </div>

                    <div class="syt-admin-card">
                        <h2>Kategori Yönetimi</h2>

                        <div class="syt-admin-field">
                            <label for="syt_categories">Kategori listesi</label>
                            <?php syt_categories_field(); ?>
                            <p class="syt-admin-hint">Her satıra bir kategori yazın. Önerilen: Futbol, Basketbol, Voleybol, Tenis.</p>
                        </div>

                        <div class="syt-admin-actions">
                            <?php submit_button('Ayarları Kaydet', 'primary', 'submit', false); ?>
                        </div>
                    </div>
                </form>
            </div>

            <div class="syt-admin-aside">
                <div class="syt-admin-card">
                    <h2>Kısa Kod</h2>
                    <p class="syt-admin-hint">Bu kısa kodu sayfa veya yazı içinde kullanın.</p>
                    <input type="text" id="syt-shortcode" class="syt-shortcode-field" value="[spor_yayin_takvimi]" readonly />
                    <div class="syt-admin-actions">
                        <button class="button button-secondary syt-copy-btn" data-target="#syt-shortcode">Kopyala</button>
                    </div>
                    <div class="syt-copy-status"></div>
                </div>

                <div class="syt-admin-card">
                    <h2>Önbellek</h2>
                    <p class="syt-admin-hint">Yayınlar güncellenmediyse önbelleği temizleyin.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('syt_clear_cache'); ?>
                        <input type="hidden" name="action" value="syt_clear_cache" />
                        <?php submit_button('Tüm önbelleği temizle', 'secondary', 'submit', false); ?>
                    </form>
                </div>

                <div class="syt-admin-card">
                    <h2>İpuçları</h2>
                    <p class="syt-admin-hint">İçerik görünmüyorsa önce önbelleği temizlemeyi deneyin.</p>
                    <p class="syt-admin-hint">Kategori isimleri veri kaynağındaki spor adlarıyla uyumlu olmalıdır.</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}
