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

function syt_sport_slug($sport) {
    $sport = syt_normalize_turkish($sport);
    $sport = strtolower($sport);
    $sport = preg_replace('/[^a-z0-9]+/', '-', $sport);
    $sport = trim($sport, '-');

    return $sport ? $sport : 'diger';
}

function syt_is_no_broadcast($channel) {
    $normalized = syt_normalize_turkish($channel);
    $normalized = strtolower(trim($normalized));

    return $normalized === 'yayin yok';
}

function syt_render_matches_table($matches) {
    if (empty($matches)) {
        return '<tr class="syt-empty"><td colspan="5">Bu tarihte yayın bilgisi bulunamadı.</td></tr>';
    }

    $matches = syt_sort_matches_by_time($matches);
    $groups = array();

    foreach ($matches as $match) {
        $time_key = isset($match['time']) ? $match['time'] : '';
        if (!isset($groups[$time_key])) {
            $groups[$time_key] = array();
        }
        $groups[$time_key][] = $match;
    }

    $html = '';

    foreach ($groups as $time => $items) {
        $rowspan = count($items);

        foreach ($items as $index => $match) {
            $sport = isset($match['sport']) ? trim($match['sport']) : '';
            $sport_class = 'sport-' . syt_sport_slug($sport);
            $match_name = isset($match['match']) ? $match['match'] : '';
            $league = isset($match['league']) ? $match['league'] : '';
            $url = isset($match['url']) ? $match['url'] : '';
            $channels = isset($match['channels']) ? (array) $match['channels'] : array();

            $html .= '<tr class="syt-row" data-sport="' . esc_attr(syt_sport_slug($sport)) . '">';

            if ($index === 0) {
                $html .= '<td class="syt-time" rowspan="' . esc_attr($rowspan) . '">';
                $html .= '<span class="syt-time-badge">' . esc_html($time) . '</span>';
                $html .= '</td>';
            }

            $html .= '<td class="syt-sport">';
            $html .= '<span class="syt-sport-badge ' . esc_attr($sport_class) . '">' . esc_html($sport) . '</span>';
            $html .= '</td>';

            $html .= '<td class="syt-match">';
            if (!empty($url)) {
                $html .= '<a class="syt-match-link" href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($match_name) . '</a>';
            } else {
                $html .= esc_html($match_name);
            }
            $html .= '</td>';

            $html .= '<td class="syt-league">' . esc_html($league) . '</td>';

            $html .= '<td class="syt-channels">';
            if (!empty($channels)) {
                foreach ($channels as $channel) {
                    $classes = 'syt-channel-badge';
                    if (syt_is_no_broadcast($channel)) {
                        $classes .= ' is-muted';
                    }
                    $html .= '<span class="' . esc_attr($classes) . '">' . esc_html($channel) . '</span>';
                }
            }
            $html .= '</td>';

            $html .= '</tr>';
        }
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
    $search_enabled = (bool) get_option('syt_enable_search', 1);

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

    $inline_data = array(
        'date' => $date,
        'matches' => $matches,
        'activeSport' => $initial_sport_slug,
        'searchEnabled' => $search_enabled,
        'searchQuery' => '',
    );

    wp_add_inline_script(
        'syt-script',
        'window.SYT = window.SYT || {}; SYT.instances = SYT.instances || {}; SYT.instances["' . esc_js($instance_id) . '"] = ' . wp_json_encode($inline_data) . ';',
        'before'
    );

    $today = syt_today_date();
    $tomorrow = syt_tomorrow_date();

    $sports = syt_get_sports_from_matches($matches);

    $buttons = array();
    if (in_array('Futbol', $sports, true)) {
        $buttons[] = 'Futbol';
        $sports = array_values(array_diff($sports, array('Futbol')));
    }
    sort($sports, SORT_STRING | SORT_FLAG_CASE);
    $buttons = array_merge($buttons, $sports);

    $button_slugs = array();
    foreach ($buttons as $button_label) {
        $button_slugs[] = syt_sport_slug($button_label);
    }

    $active_filter = $initial_sport_slug;
    if ($active_filter !== 'all' && !in_array($active_filter, $button_slugs, true)) {
        $active_filter = 'all';
    }

    ob_start();
    ?>
    <div class="syt-wrapper" data-instance="<?php echo esc_attr($instance_id); ?>" data-date="<?php echo esc_attr($date); ?>">
        <div class="syt-header">
            <div class="syt-date-nav">
                <button type="button" class="syt-date-btn <?php echo $date === $today ? 'is-active' : ''; ?>" data-date="<?php echo esc_attr($today); ?>">Bugün</button>
                <button type="button" class="syt-date-btn <?php echo $date === $tomorrow ? 'is-active' : ''; ?>" data-date="<?php echo esc_attr($tomorrow); ?>">Yarın</button>
            </div>
            <div class="syt-filters">
                <button type="button" class="syt-filter-btn <?php echo $active_filter === 'all' ? 'is-active' : ''; ?>" data-sport="all">Tümü</button>
                <?php foreach ($buttons as $button) : ?>
                    <?php $button_slug = syt_sport_slug($button); ?>
                    <button type="button" class="syt-filter-btn <?php echo $active_filter === $button_slug ? 'is-active' : ''; ?>" data-sport="<?php echo esc_attr($button_slug); ?>">
                        <?php echo esc_html($button); ?>
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

        <div class="syt-table-wrap">
            <table class="syt-table">
                <thead>
                    <tr>
                        <th>Saat</th>
                        <th>Spor</th>
                        <th>Maç / Etkinlik</th>
                        <th>Lig / Turnuva</th>
                        <th>Kanal(lar)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo syt_render_matches_table($matches); ?>
                </tbody>
            </table>
        </div>

        <div class="syt-source">Kaynak: <a href="https://www.sporekrani.com" target="_blank" rel="noopener">sporekrani.com</a></div>
    </div>
    <?php

    return ob_get_clean();
}
