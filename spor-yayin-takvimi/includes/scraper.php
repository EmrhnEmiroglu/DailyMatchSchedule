<?php

if (!defined('ABSPATH')) {
    exit;
}

function syt_scrape($date) {
    $url = 'https://www.sporekrani.com/home/day/' . $date;

    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'user-agent' => 'WordPress; Spor Yayın Takvimi',
    ));

    if (is_wp_error($response)) {
        return new WP_Error('syt_request_failed', 'Kaynak siteye ulaşılamadı.');
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return new WP_Error('syt_http_error', 'Kaynak siteden geçersiz yanıt alındı.');
    }

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        return new WP_Error('syt_empty_body', 'Kaynak siteden boş içerik alındı.');
    }

    return syt_parse_html($body, $date);
}

function syt_parse_html($html, $date) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//a[contains(@href, "/home/match/")]');

    $matches = array();
    $seen = array();

    foreach ($nodes as $node) {
        $href = $node->getAttribute('href');
        if (!$href) {
            continue;
        }

        $url = (strpos($href, 'http') === 0) ? $href : 'https://www.sporekrani.com' . $href;
        if (isset($seen[$url])) {
            continue;
        }
        $seen[$url] = true;

        $img_nodes = $xpath->query('.//img[@alt]', $node);
        $alts = array();
        foreach ($img_nodes as $img) {
            $alt = trim($img->getAttribute('alt'));
            if ($alt !== '') {
                $alts[] = $alt;
            }
        }

        $sport = isset($alts[0]) ? $alts[0] : '';
        $channels = array();
        if (count($alts) > 1) {
            $channels = array_slice($alts, 1);
            $channels = array_values(array_unique($channels));
        }

        $raw_texts = syt_get_text_nodes($node);
        $texts = array();
        $time = '';

        foreach ($raw_texts as $raw) {
            $clean = trim(preg_replace('/\s+/', ' ', $raw));
            if ($clean === '') {
                continue;
            }

            if ($time === '' && preg_match('/\b\d{2}:\d{2}\b/', $clean, $match)) {
                $time = $match[0];
                $clean = trim(str_replace($match[0], '', $clean));
                if ($clean === '') {
                    continue;
                }
            }

            $texts[] = $clean;
        }

        $match_name = isset($texts[0]) ? $texts[0] : '';
        $league = isset($texts[1]) ? $texts[1] : '';

        if ($time === '' || $match_name === '') {
            continue;
        }

        if (empty($channels)) {
            $channels = array('Yayın Yok');
        }

        $matches[] = array(
            'time' => $time,
            'sport' => $sport,
            'match' => $match_name,
            'league' => $league,
            'channels' => $channels,
            'url' => $url,
            'date' => $date,
        );
    }

    return $matches;
}

function syt_get_text_nodes($node) {
    $texts = array();

    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            $texts[] = $child->nodeValue;
        } elseif ($child->nodeType === XML_ELEMENT_NODE) {
            $texts = array_merge($texts, syt_get_text_nodes($child));
        }
    }

    return $texts;
}
