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

    function extractSports(matches) {
        var map = {};
        matches.forEach(function (match) {
            if (!match.sport) {
                return;
            }
            var label = String(match.sport).trim();
            if (!label) {
                return;
            }
            var slug = sportSlug(label);
            if (!map[slug]) {
                map[slug] = label;
            }
        });

        return Object.keys(map).map(function (slug) {
            return { slug: slug, label: map[slug] };
        });
    }

    function buildFilterButtons($wrap, matches, activeSport) {
        var sports = extractSports(matches);
        var ordered = [];

        var futbol = sports.filter(function (item) {
            return item.slug === 'futbol';
        });
        if (futbol.length) {
            ordered.push(futbol[0]);
            sports = sports.filter(function (item) {
                return item.slug !== 'futbol';
            });
        }

        sports.sort(function (a, b) {
            return a.label.localeCompare(b.label, 'tr');
        });

        ordered = ordered.concat(sports);

        if (activeSport !== 'all' && !ordered.some(function (item) { return item.slug === activeSport; })) {
            activeSport = 'all';
        }

        var html = '';
        html += '<button type="button" class="syt-filter-btn' + (activeSport === 'all' ? ' is-active' : '') + '" data-sport="all">Tümü</button>';
        ordered.forEach(function (item) {
            var active = activeSport === item.slug ? ' is-active' : '';
            html += '<button type="button" class="syt-filter-btn' + active + '" data-sport="' + escapeHtml(item.slug) + '">' + escapeHtml(item.label) + '</button>';
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
                var $tr = $('<tr class="syt-row"></tr>').attr('data-sport', sportSlug(match.sport || ''));

                if (index === 0) {
                    $tr.append('<td class="syt-time" rowspan="' + rowspan + '"><span class="syt-time-badge">' + escapeHtml(time) + '</span></td>');
                }

                var sportClass = 'sport-' + sportSlug(match.sport || '');
                $tr.append('<td class="syt-sport"><span class="syt-sport-badge ' + sportClass + '">' + escapeHtml(match.sport || '') + '</span></td>');

                var matchHtml = '';
                if (match.url) {
                    matchHtml = '<a class="syt-match-link" href="' + escapeHtml(match.url) + '" target="_blank" rel="noopener">' + escapeHtml(match.match || '') + '</a>';
                } else {
                    matchHtml = escapeHtml(match.match || '');
                }
                $tr.append('<td class="syt-match">' + matchHtml + '</td>');

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
            activeSport: data.activeSport || 'all',
            searchQuery: normalizeQuery(data.searchQuery || '')
        };

        state.activeSport = buildFilterButtons($wrap, state.matches, state.activeSport);
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

                        state.activeSport = buildFilterButtons($wrap, state.matches, state.activeSport);
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
