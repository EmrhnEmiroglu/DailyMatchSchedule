<?php
/**
 * Plugin Name: Spor Yayın Takvimi
 * Description: sporekrani.com günlük spor yayın takvimini WordPress sayfalarında gösterir.
 * Version: 1.0.2
 * Author: EmrhnEmiroglu
 * License: GPL-2.0+
 * Text Domain: spor-yayin-takvimi
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SYT_VERSION', '1.0.2');
define('SYT_PATH', plugin_dir_path(__FILE__));
define('SYT_URL', plugin_dir_url(__FILE__));

require_once SYT_PATH . 'includes/cache.php';
require_once SYT_PATH . 'includes/scraper.php';
require_once SYT_PATH . 'includes/shortcode.php';
require_once SYT_PATH . 'includes/admin.php';

function syt_activate() {
    if (get_option('syt_cache_duration', null) === null) {
        add_option('syt_cache_duration', 3600);
    }
}
register_activation_hook(__FILE__, 'syt_activate');

function syt_deactivate() {
    syt_clear_all_cache();
}
register_deactivation_hook(__FILE__, 'syt_deactivate');

function syt_register_assets() {
    $css_path = SYT_PATH . 'assets/css/style.css';
    $js_path = SYT_PATH . 'assets/js/script.js';
    $css_version = file_exists($css_path) ? filemtime($css_path) : SYT_VERSION;
    $js_version = file_exists($js_path) ? filemtime($js_path) : SYT_VERSION;

    wp_register_style('syt-style', SYT_URL . 'assets/css/style.css', array(), $css_version);
    wp_register_script('syt-script', SYT_URL . 'assets/js/script.js', array('jquery'), $js_version, true);
}
add_action('wp_enqueue_scripts', 'syt_register_assets');

function syt_enqueue_assets() {
    wp_enqueue_style('syt-style');
    wp_enqueue_script('syt-script');

    $strings = array(
        'loading' => 'Yükleniyor...',
        'no_data' => 'Bu tarihte yayın bilgisi bulunamadı.',
        'no_filter' => 'Seçilen spor dalı için yayın bulunamadı.',
        'no_search' => 'Arama için yayın bulunamadı.',
        'server_error' => 'Sunucuya ulaşılamadı. Lütfen tekrar deneyin.',
    );

    wp_localize_script('syt-script', 'SYT', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('syt_nonce'),
        'strings' => $strings,
    ));
}

function syt_ajax_get_matches() {
    check_ajax_referer('syt_nonce', 'nonce');

    $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';

    if (!syt_is_valid_date($date)) {
        wp_send_json_error('Geçersiz tarih formatı.');
    }

    $matches = syt_get_matches($date);
    if (is_wp_error($matches)) {
        wp_send_json_error($matches->get_error_message());
    }

    wp_send_json_success($matches);
}
add_action('wp_ajax_syt_get_matches', 'syt_ajax_get_matches');
add_action('wp_ajax_nopriv_syt_get_matches', 'syt_ajax_get_matches');

add_shortcode('spor_yayin_takvimi', 'syt_shortcode');
