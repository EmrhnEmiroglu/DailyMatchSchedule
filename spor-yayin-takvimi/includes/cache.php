<?php

if (!defined('ABSPATH')) {
    exit;
}

function syt_cache_key($date) {
    return 'syt_matches_' . str_replace('-', '', $date);
}

function syt_get_cache($date) {
    return get_transient(syt_cache_key($date));
}

function syt_set_cache($date, $data, $ttl) {
    return set_transient(syt_cache_key($date), $data, $ttl);
}

function syt_delete_cache($date) {
    return delete_transient(syt_cache_key($date));
}

function syt_clear_all_cache() {
    global $wpdb;

    $like = $wpdb->esc_like('_transient_syt_matches_') . '%';
    $timeout_like = $wpdb->esc_like('_transient_timeout_syt_matches_') . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $like,
            $timeout_like
        )
    );

    if (is_multisite()) {
        $site_like = $wpdb->esc_like('_site_transient_syt_matches_') . '%';
        $site_timeout_like = $wpdb->esc_like('_site_transient_timeout_syt_matches_') . '%';

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                $site_like,
                $site_timeout_like
            )
        );
    }
}
