<?php

if (!defined('ABSPATH')) {
    exit;
}

function syt_is_valid_date($date) {
    return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
}

function syt_today_date() {
    if (function_exists('wp_date')) {
        return wp_date('Y-m-d');
    }

    return date_i18n('Y-m-d');
}

function syt_tomorrow_date() {
    $ts = current_time('timestamp');
    $tomorrow = $ts + DAY_IN_SECONDS;

    if (function_exists('wp_date')) {
        return wp_date('Y-m-d', $tomorrow);
    }

    return date_i18n('Y-m-d', $tomorrow);
}

function syt_get_matches($date) {
    if (!syt_is_valid_date($date)) {
        return new WP_Error('syt_invalid_date', 'Geçersiz tarih formatı.');
    }

    $cached = syt_get_cache($date);
    if ($cached !== false) {
        return $cached;
    }

    $matches = syt_scrape($date);
    if (is_wp_error($matches)) {
        return $matches;
    }

    $ttl = (int) get_option('syt_cache_duration', 3600);
    if ($ttl <= 0) {
        $ttl = 3600;
    }

    syt_set_cache($date, $matches, $ttl);

    return $matches;
}

function syt_sort_matches_by_time($matches) {
    usort($matches, function ($a, $b) {
        $time_a = isset($a['time']) ? $a['time'] : '';
        $time_b = isset($b['time']) ? $b['time'] : '';

        return strcmp($time_a, $time_b);
    });

    return $matches;
}

function syt_get_sports_from_matches($matches) {
    $sports = array();
    foreach ($matches as $match) {
        if (!empty($match['sport'])) {
            $sport = trim($match['sport']);
            if ($sport !== '') {
                $sports[$sport] = true;
            }
        }
    }

    return array_keys($sports);
}

function syt_normalize_turkish($text) {
    $map = array(
        'I' => 'i',
        'İ' => 'i',
        'ı' => 'i',
        'Ş' => 's',
        'ş' => 's',
        'Ğ' => 'g',
        'ğ' => 'g',
        'Ü' => 'u',
        'ü' => 'u',
        'Ö' => 'o',
        'ö' => 'o',
        'Ç' => 'c',
        'ç' => 'c',
    );

    return strtr($text, $map);
}

function syt_normalize_key($text) {
    $text = syt_normalize_turkish($text);
    $text = strtolower(trim($text));
    $text = preg_replace('/\s+/', ' ', $text);
    $text = str_replace('voelybol', 'voleybol', $text);

    return $text;
}

function syt_fix_turkish_text($text) {
    $text = (string) $text;
    if ($text === '') {
        return $text;
    }

    $replacements = array(
        'spor programi' => 'Spor Programı',
        'futbol programi' => 'Futbol Programı',
        'basketbol programi' => 'Basketbol Programı',
        'voleybol programi' => 'Voleybol Programı',
        'tenis programi' => 'Tenis Programı',
        'gunun yarislari' => 'Günün Yarışları',
        'günün yarislari' => 'Günün Yarışları',
        'yarislari' => 'Yarışları',
        'yarislar' => 'Yarışlar',
        'yaris' => 'Yarış',
        'programi' => 'Programı',
    );

    foreach ($replacements as $search => $replace) {
        $text = str_ireplace($search, $replace, $text);
    }

    return $text;
}

function syt_sport_slug($sport) {
    $sport = syt_normalize_key($sport);
    $sport = preg_replace('/[^a-z0-9]+/', '-', $sport);
    $sport = trim($sport, '-');

    return $sport ? $sport : 'diger';
}

function syt_default_categories() {
    return array('Futbol', 'Basketbol', 'Voelybol', 'Tenis');
}

function syt_get_categories() {
    $raw = get_option('syt_categories', '');
    if (!is_string($raw) || trim($raw) === '') {
        return syt_default_categories();
    }

    $parts = preg_split('/\r\n|\r|\n|,|;/', $raw);
    $labels = array();

    foreach ($parts as $part) {
        $label = trim($part);
        if ($label !== '') {
            $labels[] = $label;
        }
    }

    return empty($labels) ? syt_default_categories() : $labels;
}

function syt_sport_emoji($sport_slug) {
    $map = array(
        'futbol' => '⚽',
        'basketbol' => '🏀',
        'voleybol' => '🏐',
        'voelybol' => '🏐',
        'tenis' => '🎾',
        'programlar' => '📺',
    );

    return isset($map[$sport_slug]) ? $map[$sport_slug] : '';
}

function syt_display_league($league) {
    return syt_fix_turkish_text($league);
}

function syt_display_channel($channel) {
    $channel = trim((string) $channel);
    if ($channel === '') {
        return $channel;
    }

    $channel = preg_replace('/yildizi/i', 'Yıldızı', $channel);
    $channel = preg_replace('/yildiz/i', 'Yıldız', $channel);
    $channel = syt_fix_turkish_text($channel);

    return $channel;
}

function syt_get_category_items() {
    $categories = syt_get_categories();
    $items = array();

    foreach ($categories as $label) {
        $slug = syt_sport_slug($label);
        $items[] = array(
            'label' => $label,
            'slug' => $slug,
            'emoji' => syt_sport_emoji($slug),
        );
    }

    return $items;
}

function syt_is_program_item($sport, $league) {
    $sport_key = syt_normalize_key($sport);
    $league_key = syt_normalize_key($league);

    if ($sport_key !== '' && strpos($sport_key, 'program') !== false) {
        return true;
    }

    if ($league_key !== '' && strpos($league_key, 'spor programi') !== false) {
        return true;
    }

    return false;
}

function syt_match_category_slug($sport, $league, $categories) {
    if (syt_is_program_item($sport, $league)) {
        return 'programlar';
    }

    $sport_key = syt_normalize_key($sport);
    if ($sport_key === '') {
        return 'diger';
    }

    foreach ($categories as $category) {
        $label = isset($category['label']) ? $category['label'] : '';
        $slug = isset($category['slug']) ? $category['slug'] : '';
        $cat_key = syt_normalize_key($label);

        if ($cat_key === '') {
            continue;
        }

        if (strpos($sport_key, $cat_key) !== false || strpos($cat_key, $sport_key) !== false) {
            return $slug !== '' ? $slug : syt_sport_slug($label);
        }
    }

    return 'diger';
}

function syt_is_no_broadcast($channel) {
    $normalized = syt_normalize_turkish($channel);
    $normalized = strtolower(trim($normalized));

    return $normalized === 'yayin yok';
}

function syt_render_matches_table($matches, $categories = array()) {
    if (empty($matches)) {
        return '<div class="syt-empty">Bu tarihte yayın bilgisi bulunamadı.</div>';
    }

    $matches = syt_sort_matches_by_time($matches);
    $groups = array();
    $categories = !empty($categories) ? $categories : syt_get_category_items();

    foreach ($matches as $match) {
        $time_key = isset($match['time']) ? $match['time'] : '';
        if (!isset($groups[$time_key])) {
            $groups[$time_key] = array();
        }
        $groups[$time_key][] = $match;
    }

    $html = '';

    foreach ($groups as $time => $items) {
        $html .= '<div class="syt-group">';
        $html .= '<div class="syt-time-col">';
        $html .= '<span class="syt-time-icon">🕒</span>';
        $html .= '<span class="syt-time-text">' . esc_html($time) . '</span>';
        $html .= '</div>';
        $html .= '<div class="syt-group-items">';

        foreach ($items as $match) {
            $sport = isset($match['sport']) ? trim($match['sport']) : '';
            $match_name = isset($match['match']) ? $match['match'] : '';
            $league = isset($match['league']) ? $match['league'] : '';
            $channels = isset($match['channels']) ? (array) $match['channels'] : array();

            $category_slug = syt_match_category_slug($sport, $league, $categories);
            $sport_emoji = syt_sport_emoji($category_slug);
            $html .= '<div class="syt-item syt-row" data-sport="' . esc_attr($category_slug) . '">';
            $html .= '<div class="syt-item-icon">' . esc_html($sport_emoji ? $sport_emoji : '•') . '</div>';
            $html .= '<div class="syt-item-content">';
            $html .= '<div class="syt-item-title">' . esc_html(syt_fix_turkish_text($match_name)) . '</div>';
            if (!empty($league)) {
                $html .= '<div class="syt-item-sub">' . esc_html(syt_display_league($league)) . '</div>';
            }
            $html .= '</div>';

            $html .= '<div class="syt-item-channels">';
            if (!empty($channels)) {
                foreach ($channels as $channel) {
                    $classes = 'syt-channel-badge';
                    if (syt_is_no_broadcast($channel)) {
                        $classes .= ' is-muted';
                    }
                    $html .= '<span class="' . esc_attr($classes) . '">' . esc_html(syt_display_channel($channel)) . '</span>';
                }
            }
            $html .= '</div>';
            $html .= '<div class="syt-item-arrow" aria-hidden="true">›</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
    }

    return $html;
}

function syt_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'date' => syt_today_date(),
            'sport' => '',
        ),
        $atts,
        'spor_yayin_takvimi'
    );

    $date = sanitize_text_field($atts['date']);
    if (!syt_is_valid_date($date)) {
        $date = syt_today_date();
    }

    $sport = sanitize_text_field($atts['sport']);
    $default_sport = sanitize_text_field(get_option('syt_default_sport', ''));
    $search_enabled = false;

    $matches = syt_get_matches($date);
    $error_message = '';

    if (is_wp_error($matches)) {
        $error_message = $matches->get_error_message();
        $matches = array();
    }

    syt_enqueue_assets();

    static $instance = 0;
    $instance++;
    $instance_id = 'syt-' . $instance;

    $initial_sport = $sport !== '' ? $sport : $default_sport;
    $initial_sport = trim($initial_sport);
    $initial_sport_slug = $initial_sport === '' ? 'all' : syt_sport_slug($initial_sport);

    $categories = syt_get_category_items();
    $bg_color = get_option('syt_background_color', '#f4f6f9');
    $bg_color = sanitize_hex_color($bg_color);
    if (!$bg_color) {
        $bg_color = '#f4f6f9';
    }

    $inline_data = array(
        'date' => $date,
        'matches' => $matches,
        'activeSport' => $initial_sport_slug,
        'searchEnabled' => $search_enabled,
        'searchQuery' => '',
        'categories' => $categories,
    );

    wp_add_inline_script(
        'syt-script',
        'window.SYT = window.SYT || {}; SYT.instances = SYT.instances || {}; SYT.instances["' . esc_js($instance_id) . '"] = ' . wp_json_encode($inline_data) . ';',
        'before'
    );

    $today = syt_today_date();
    $tomorrow = syt_tomorrow_date();

    $button_slugs = array();
    foreach ($categories as $category) {
        $button_slugs[] = $category['slug'];
    }

    $active_filter = $initial_sport_slug;
    if ($active_filter !== 'all' && !in_array($active_filter, $button_slugs, true)) {
        $active_filter = 'all';
    }

    ob_start();
    ?>
    <div class="syt-wrapper" data-instance="<?php echo esc_attr($instance_id); ?>" data-date="<?php echo esc_attr($date); ?>" style="--syt-bg: <?php echo esc_attr($bg_color); ?>;">
        <div class="syt-header">
            <div class="syt-date-nav">
                <button type="button" class="syt-date-btn <?php echo $date === $today ? 'is-active' : ''; ?>" data-date="<?php echo esc_attr($today); ?>">Bugün</button>
                <button type="button" class="syt-date-btn <?php echo $date === $tomorrow ? 'is-active' : ''; ?>" data-date="<?php echo esc_attr($tomorrow); ?>">Yarın</button>
            </div>
            <div class="syt-filters">
                <button type="button" class="syt-filter-btn <?php echo $active_filter === 'all' ? 'is-active' : ''; ?>" data-sport="all">Tümü</button>
                <?php foreach ($categories as $category) : ?>
                    <?php
                    $button_slug = $category['slug'];
                    $button_label = $category['label'];
                    $button_emoji = $category['emoji'];
                    $button_text = $button_emoji ? $button_emoji . ' ' . $button_label : $button_label;
                    ?>
                    <button type="button" class="syt-filter-btn <?php echo $active_filter === $button_slug ? 'is-active' : ''; ?>" data-sport="<?php echo esc_attr($button_slug); ?>">
                        <?php echo esc_html($button_text); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php if ($search_enabled) : ?>
                <div class="syt-search">
                    <input type="text" class="syt-search-input" placeholder="Takım, lig veya kanal ara" />
                </div>
            <?php endif; ?>
        </div>

        <div class="syt-status" role="status" aria-live="polite">
            <?php if (!empty($error_message)) : ?>
                <span class="syt-error"><?php echo esc_html($error_message); ?></span>
            <?php endif; ?>
        </div>

        <div class="syt-list">
            <?php echo syt_render_matches_table($matches, $categories); ?>
        </div>

    </div>
    <?php

    return ob_get_clean();
}
