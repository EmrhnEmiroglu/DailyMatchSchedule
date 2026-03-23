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

    function normalizeQuery(text) {
        return normalizeTurkish(text).toLowerCase().trim();
    }

    function sportSlug(sport) {
        if (!sport) {
            return 'diger';
        }
        var slug = normalizeTurkish(sport);
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
        var normalized = normalizeTurkish(channel).toLowerCase().trim();
        return normalized === 'yayin yok';
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
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

    function renderMatches($wrap, matches, emptyMessage) {
        var $tbody = $wrap.find('.syt-table tbody');
        $tbody.empty();

        if (!matches || matches.length === 0) {
            var message = emptyMessage || SYT.strings.no_data;
            $tbody.append('<tr class="syt-empty"><td colspan="5">' + escapeHtml(message) + '</td></tr>');
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
            var rowspan = items.length;

            items.forEach(function (match, index) {
                var sportSlugValue = sportSlug(match.sport || '');
                var $tr = $('<tr class="syt-row"></tr>').attr('data-sport', sportSlugValue);

                if (index === 0) {
                    $tr.append('<td class="syt-time" rowspan="' + rowspan + '"><span class="syt-time-badge">' + escapeHtml(time) + '</span></td>');
                }

                var sportClass = 'sport-' + sportSlugValue;
                var sportLabel = match.sport || '';
                var emoji = emojiForSlug(sportSlugValue);
                var sportText = emoji ? emoji + ' ' + sportLabel : sportLabel;

                $tr.append('<td class="syt-sport"><span class="syt-sport-badge ' + sportClass + '">' + escapeHtml(sportText) + '</span></td>');

                $tr.append('<td class="syt-match">' + escapeHtml(match.match || '') + '</td>');

                $tr.append('<td class="syt-league">' + escapeHtml(match.league || '') + '</td>');

                var channelsHtml = '';
                if (match.channels && match.channels.length) {
                    match.channels.forEach(function (channel) {
                        var classes = 'syt-channel-badge';
                        if (isNoBroadcast(channel)) {
                            classes += ' is-muted';
                        }
                        channelsHtml += '<span class="' + classes + '">' + escapeHtml(channel) + '</span>';
                    });
                }
                $tr.append('<td class="syt-channels">' + channelsHtml + '</td>');

                $tbody.append($tr);
            });
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
        if (!query) {
            return true;
        }

        var parts = [];
        if (match.match) {
            parts.push(match.match);
        }
        if (match.league) {
            parts.push(match.league);
        }
        if (match.sport) {
            parts.push(match.sport);
        }
        if (match.channels && match.channels.length) {
            parts = parts.concat(match.channels);
        }

        var haystack = normalizeQuery(parts.join(' '));
        return haystack.indexOf(query) !== -1;
    }

    function applyFilters(matches, activeSport, searchQuery) {
        return matches.filter(function (match) {
            var sportOk = activeSport === 'all' || sportSlug(match.sport || '') === activeSport;
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
            searchQuery: normalizeQuery(data.searchQuery || '')
        };

        state.activeSport = buildFilterButtons($wrap, state.categories, state.matches, state.activeSport);
        var filtered = applyFilters(state.matches, state.activeSport, state.searchQuery);
        renderMatches($wrap, filtered, getEmptyMessage(state));

        var $searchInput = $wrap.find('.syt-search-input');
        if ($searchInput.length && data.searchQuery) {
            $searchInput.val(data.searchQuery);
        }

        $wrap.on('input', '.syt-search-input', function () {
            state.searchQuery = normalizeQuery($(this).val());
            var nextFiltered = applyFilters(state.matches, state.activeSport, state.searchQuery);
            renderMatches($wrap, nextFiltered, getEmptyMessage(state));
        });

        $wrap.on('click', '.syt-filter-btn', function () {
            var $btn = $(this);
            $wrap.find('.syt-filter-btn').removeClass('is-active');
            $btn.addClass('is-active');

            state.activeSport = $btn.data('sport') || 'all';
            var nextFiltered = applyFilters(state.matches, state.activeSport, state.searchQuery);
            renderMatches($wrap, nextFiltered, getEmptyMessage(state));
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
                        var nextFiltered = applyFilters(state.matches, state.activeSport, state.searchQuery);
                        renderMatches($wrap, nextFiltered, getEmptyMessage(state));
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
