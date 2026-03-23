(function ($) {
    function normalizeTurkish(text) {
        return String(text || '')
            .replace(/İ/g, 'i')
            .replace(/I/g, 'i')
            .replace(/ı/g, 'i')
            .replace(/Ş/g, 's')
            .replace(/ş/g, 's')
            .replace(/Ğ/g, 'g')
            .replace(/ğ/g, 'g')
            .replace(/Ü/g, 'u')
            .replace(/ü/g, 'u')
            .replace(/Ö/g, 'o')
            .replace(/ö/g, 'o')
            .replace(/Ç/g, 'c')
            .replace(/ç/g, 'c');
    }

    function normalizeKey(text) {
        var value = normalizeTurkish(text).toLowerCase().trim();
        value = value.replace(/\s+/g, ' ');
        value = value.replace(/voelybol/g, 'voleybol');
        return value;
    }

    function normalizeQuery(text) {
        return normalizeKey(text);
    }

    function sportSlug(sport) {
        if (!sport) {
            return 'diger';
        }
        var slug = normalizeKey(sport);
        slug = slug.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        slug = slug.replace(/^-+|-+$/g, '');
        return slug || 'diger';
    }

    function emojiForSlug(slug) {
        var map = {
            futbol: '⚽',
            basketbol: '🏀',
            voleybol: '🏐',
            voelybol: '🏐',
            tenis: '🎾'
        };
        return map[slug] || '';
    }

    function isNoBroadcast(channel) {
        if (!channel) {
            return false;
        }
        var normalized = normalizeKey(channel).trim();
        return normalized === 'yayin yok';
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }

    function displayLeague(league) {
        var normalized = normalizeKey(league);
        if (normalized === 'spor programi') {
            return 'Spor Programı';
        }
        return league || '';
    }

    function displayChannel(channel) {
        var value = String(channel || '').trim();
        if (!value) {
            return '';
        }

        value = value.replace(/yildizi/gi, 'Yıldızı');
        value = value.replace(/yildiz/gi, 'Yıldız');
        value = value.replace(/spor programi/gi, 'Spor Programı');

        return value;
    }

    function normalizeCategories(categories, matches) {
        var list = [];

        if (Array.isArray(categories) && categories.length) {
            categories.forEach(function (item) {
                var label = '';
                var slug = '';
                var emoji = '';

                if (typeof item === 'string') {
                    label = item;
                } else if (item) {
                    label = item.label || '';
                    slug = item.slug || '';
                    emoji = item.emoji || '';
                }

                label = String(label || '').trim();
                if (!label) {
                    return;
                }

                slug = slug || sportSlug(label);
                emoji = emoji || emojiForSlug(slug);

                list.push({
                    label: label,
                    slug: slug,
                    emoji: emoji
                });
            });

            return list;
        }

        var map = {};
        (matches || []).forEach(function (match) {
            if (!match.sport) {
                return;
            }
            var label = String(match.sport).trim();
            if (!label) {
                return;
            }
            var slug = sportSlug(label);
            if (!map[slug]) {
                map[slug] = {
                    label: label,
                    slug: slug,
                    emoji: emojiForSlug(slug)
                };
            }
        });

        return Object.keys(map).map(function (key) {
            return map[key];
        });
    }

    function categorySlugForMatch(match, categories) {
        var sport = match && match.sport ? match.sport : '';
        var sportKey = normalizeKey(sport);

        if (!sportKey) {
            return 'diger';
        }

        var list = normalizeCategories(categories, []);
        for (var i = 0; i < list.length; i += 1) {
            var category = list[i];
            var categoryKey = normalizeKey(category.label || '');
            if (!categoryKey) {
                continue;
            }
            if (sportKey.indexOf(categoryKey) !== -1 || categoryKey.indexOf(sportKey) !== -1) {
                return category.slug || sportSlug(category.label || '');
            }
        }

        return 'diger';
    }

    function buildFilterButtons($wrap, categories, matches, activeSport) {
        var ordered = normalizeCategories(categories, matches);

        if (activeSport !== 'all' && !ordered.some(function (item) { return item.slug === activeSport; })) {
            activeSport = 'all';
        }

        var html = '';
        html += '<button type="button" class="syt-filter-btn' + (activeSport === 'all' ? ' is-active' : '') + '" data-sport="all">Tümü</button>';
        ordered.forEach(function (item) {
            var active = activeSport === item.slug ? ' is-active' : '';
            var label = item.emoji ? item.emoji + ' ' + item.label : item.label;
            html += '<button type="button" class="syt-filter-btn' + active + '" data-sport="' + escapeHtml(item.slug) + '">' + escapeHtml(label) + '</button>';
        });

        $wrap.find('.syt-filters').html(html);
        return activeSport;
    }

    function groupByTime(matches) {
        var groups = {};
        matches.forEach(function (match) {
            var time = match.time || '';
            if (!groups[time]) {
                groups[time] = [];
            }
            groups[time].push(match);
        });
        return groups;
    }

    function renderMatches($wrap, matches, categories, emptyMessage) {
        var $list = $wrap.find('.syt-list');
        $list.empty();

        if (!matches || matches.length === 0) {
            var message = emptyMessage || SYT.strings.no_data;
            $list.append('<div class="syt-empty">' + escapeHtml(message) + '</div>');
            return;
        }

        matches = matches.slice().sort(function (a, b) {
            var ta = a.time || '';
            var tb = b.time || '';
            return ta.localeCompare(tb);
        });

        var groups = groupByTime(matches);
        Object.keys(groups).sort().forEach(function (time) {
            var items = groups[time];
            var $group = $('<div class="syt-group"></div>');
            var $timeCol = $('<div class="syt-time-col"></div>');
            $timeCol.append('<span class="syt-time-icon">🕒</span>');
            $timeCol.append('<span class="syt-time-text">' + escapeHtml(time) + '</span>');

            var $items = $('<div class="syt-group-items"></div>');

            items.forEach(function (match) {
                var categorySlug = categorySlugForMatch(match, categories);
                var emoji = emojiForSlug(categorySlug) || '•';
                var $row = $('<div class="syt-item syt-row"></div>').attr('data-sport', categorySlug);

                $row.append('<div class="syt-item-icon">' + escapeHtml(emoji) + '</div>');

                var $content = $('<div class="syt-item-content"></div>');
                $content.append('<div class="syt-item-title">' + escapeHtml(match.match || '') + '</div>');
                if (match.league) {
                    $content.append('<div class="syt-item-sub">' + escapeHtml(displayLeague(match.league)) + '</div>');
                }
                $row.append($content);

                var channelsHtml = '';
                if (match.channels && match.channels.length) {
                    match.channels.forEach(function (channel) {
                        var classes = 'syt-channel-badge';
                        if (isNoBroadcast(channel)) {
                            classes += ' is-muted';
                        }
                        channelsHtml += '<span class="' + classes + '">' + escapeHtml(displayChannel(channel)) + '</span>';
                    });
                }
                $row.append('<div class="syt-item-channels">' + channelsHtml + '</div>');
                $row.append('<div class="syt-item-arrow" aria-hidden="true">›</div>');

                $items.append($row);
            });

            $group.append($timeCol);
            $group.append($items);
            $list.append($group);
        });
    }

    function setStatus($wrap, message, type) {
        var $status = $wrap.find('.syt-status');
        if (!message) {
            $status.empty();
            return;
        }
        var cls = type ? 'syt-' + type : '';
        $status.html('<span class="' + cls + '">' + escapeHtml(message) + '</span>');
    }

    function matchesSearch(match, query) {
        return true;
    }

    function applyFilters(matches, categories, activeSport, searchQuery) {
        return matches.filter(function (match) {
            var categorySlug = categorySlugForMatch(match, categories);
            var sportOk = activeSport === 'all' || categorySlug === activeSport;
            var searchOk = matchesSearch(match, searchQuery);
            return sportOk && searchOk;
        });
    }

    function getEmptyMessage(state) {
        if (state.searchQuery) {
            return SYT.strings.no_search;
        }
        if (state.activeSport && state.activeSport !== 'all') {
            return SYT.strings.no_filter;
        }
        return SYT.strings.no_data;
    }

    function initInstance($wrap, data) {
        var state = {
            date: data.date || $wrap.data('date'),
            matches: data.matches || [],
            categories: data.categories || [],
            activeSport: data.activeSport || 'all',
            searchQuery: ''
        };

        state.activeSport = buildFilterButtons($wrap, state.categories, state.matches, state.activeSport);
        var filtered = applyFilters(state.matches, state.categories, state.activeSport, state.searchQuery);
        renderMatches($wrap, filtered, state.categories, getEmptyMessage(state));

        // Arama filtresi bu sürümde devre dışı.

        $wrap.on('click', '.syt-filter-btn', function () {
            var $btn = $(this);
            $wrap.find('.syt-filter-btn').removeClass('is-active');
            $btn.addClass('is-active');

            state.activeSport = $btn.data('sport') || 'all';
            var nextFiltered = applyFilters(state.matches, state.categories, state.activeSport, state.searchQuery);
            renderMatches($wrap, nextFiltered, state.categories, getEmptyMessage(state));
        });

        $wrap.on('click', '.syt-date-btn', function () {
            var $btn = $(this);
            var newDate = $btn.data('date');
            if (!newDate || newDate === state.date) {
                return;
            }

            $wrap.find('.syt-date-btn').removeClass('is-active');
            $btn.addClass('is-active');

            setStatus($wrap, SYT.strings.loading, 'loading');

            $.ajax({
                method: 'POST',
                url: SYT.ajaxurl,
                data: {
                    action: 'syt_get_matches',
                    nonce: SYT.nonce,
                    date: newDate
                }
            })
                .done(function (response) {
                    if (response && response.success) {
                        state.date = newDate;
                        state.matches = response.data || [];

                        state.activeSport = buildFilterButtons($wrap, state.categories, state.matches, state.activeSport);
                        var nextFiltered = applyFilters(state.matches, state.categories, state.activeSport, state.searchQuery);
                        renderMatches($wrap, nextFiltered, state.categories, getEmptyMessage(state));
                        setStatus($wrap, '');
                    } else {
                        var message = response && response.data ? response.data : SYT.strings.server_error;
                        setStatus($wrap, message, 'error');
                    }
                })
                .fail(function () {
                    setStatus($wrap, SYT.strings.server_error, 'error');
                });
        });
    }

    $(function () {
        $('.syt-wrapper').each(function () {
            var $wrap = $(this);
            var instanceId = $wrap.data('instance');
            var data = null;

            if (window.SYT && SYT.instances && instanceId && SYT.instances[instanceId]) {
                data = SYT.instances[instanceId];
            }

            initInstance($wrap, data || {});
        });
    });
})(jQuery);
