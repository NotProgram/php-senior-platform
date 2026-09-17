/* ==========================================================================
   Senior DevLab — Application shell behaviour
   Theme switching, explorer, command palette, shortcuts, toasts.
   No dependencies. Every handler degrades to a no-op if its DOM is absent.
   ========================================================================== */
(function () {
    'use strict';

    var STORAGE = {
        theme: 'devlab_theme',
        sidebar: 'devlab_sidebar_collapsed',
        recent: 'devlab_recent_targets'
    };

    var THEMES = [
        { id: 'devlab', label: 'Dark+ (predeterminado)' },
        { id: 'apple-glass', label: 'Apple Minimalist Glass (visionOS)' },
        { id: 'midnight', label: 'Midnight (azul profundo)' },
        { id: 'contrast', label: 'Alto contraste' },
        { id: 'daylight', label: 'Daylight (claro)' }
    ];

    function readStore(key, fallback) {
        try {
            var value = window.localStorage.getItem(key);
            return value === null ? fallback : value;
        } catch (error) {
            return fallback;
        }
    }

    function writeStore(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (error) {
            /* Storage blocked (private mode): preferences stay per-session. */
        }
    }

    /* ---------------------------------------------------------------- Theme */
    function applyTheme(themeId) {
        document.documentElement.setAttribute('data-theme', themeId);
        var label = document.getElementById('theme-label');
        if (label) {
            var match = THEMES.filter(function (t) { return t.id === themeId; })[0];
            label.textContent = match ? match.label : themeId;
        }
        document.querySelectorAll('[data-theme-option]').forEach(function (option) {
            option.classList.toggle('is-active', option.getAttribute('data-theme-option') === themeId);
        });
    }

    function initTheme() {
        applyTheme(readStore(STORAGE.theme, 'apple-glass'));

        document.querySelectorAll('[data-theme-option]').forEach(function (option) {
            option.addEventListener('click', function (event) {
                event.preventDefault();
                var next = option.getAttribute('data-theme-option');
                writeStore(STORAGE.theme, next);
                applyTheme(next);
            });
        });
    }

    /* -------------------------------------------------------------- Sidebar */
    function initSidebar() {
        var sidebar = document.getElementById('app-sidebar');
        var toggle = document.getElementById('btn-tab-toggle-sidebar');
        if (!sidebar) { return; }

        function setCollapsed(collapsed) {
            sidebar.classList.toggle('collapsed', collapsed);
            if (toggle) {
                toggle.classList.toggle('collapsed-mode', collapsed);
                toggle.setAttribute('title', (collapsed ? 'Abrir' : 'Cerrar') + ' Explorer (Ctrl+B)');
                toggle.setAttribute('aria-expanded', String(!collapsed));
            }
            writeStore(STORAGE.sidebar, collapsed ? 'true' : 'false');
        }

        if (readStore(STORAGE.sidebar, 'false') === 'true') { setCollapsed(true); }

        if (toggle) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                setCollapsed(!sidebar.classList.contains('collapsed'));
            });
        }

        window.DevLab = window.DevLab || {};
        window.DevLab.toggleSidebar = function () {
            setCollapsed(!sidebar.classList.contains('collapsed'));
        };

        /* Collapsible explorer groups. */
        document.querySelectorAll('.explorer-section-title[data-group]').forEach(function (header) {
            header.addEventListener('click', function () {
                var list = document.getElementById('group-' + header.getAttribute('data-group'));
                if (!list) { return; }
                header.classList.toggle('is-collapsed');
                list.classList.toggle('is-hidden');
            });
        });

        /* Explorer quick filter. */
        var filter = document.getElementById('sidebar-filter-input');
        if (filter) {
            filter.addEventListener('input', function () {
                var needle = normalize(filter.value);
                document.querySelectorAll('.sidebar-content .tree-item').forEach(function (item) {
                    var haystack = normalize(item.textContent);
                    item.classList.toggle('is-hidden', needle.length > 0 && haystack.indexOf(needle) === -1);
                });
                document.querySelectorAll('.sidebar-content .tree-list').forEach(function (list) {
                    var visible = list.querySelectorAll('.tree-item:not(.is-hidden)').length;
                    var header = list.previousElementSibling;
                    if (header && header.classList.contains('explorer-section-title')) {
                        header.style.display = visible === 0 && needle.length > 0 ? 'none' : '';
                    }
                });
            });
        }
    }

    /* --------------------------------------------------------------- Search */
    function normalize(value) {
        return (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function escapeHtml(value) {
        return (value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function highlight(text, terms) {
        var safe = escapeHtml(text);
        if (!terms.length) { return safe; }
        var pattern = new RegExp('(' + terms.map(function (term) {
            return term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }).join('|') + ')', 'gi');
        return safe.replace(pattern, '<mark>$1</mark>');
    }

    var ICONS = {
        lab: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="4" y1="21" y2="14"/><line x1="4" x2="4" y1="10" y2="3"/><line x1="12" x2="12" y1="21" y2="12"/><line x1="12" x2="12" y1="8" y2="3"/><line x1="20" x2="20" y1="21" y2="16"/><line x1="20" x2="20" y1="12" y2="3"/><line x1="1" x2="7" y1="14" y2="14"/><line x1="9" x2="15" y1="8" y2="8"/><line x1="17" x2="23" y1="16" y2="16"/></svg>',
        module: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
        view: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
        path: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M9 19h4a4 4 0 0 0 4-4V8"/></svg>',
        lesson: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m10 13-2 2 2 2"/><path d="m14 17 2-2-2-2"/></svg>'
    };

    function initPalette() {
        var raw = document.getElementById('search-catalog');
        var box = document.getElementById('titlebar-search-box');
        var input = document.getElementById('global-search-input');
        var palette = document.getElementById('search-palette');
        var results = document.getElementById('search-palette-results');
        var label = document.getElementById('search-palette-label');
        if (!raw || !input || !palette || !results) { return; }

        var catalog = [];
        try {
            catalog = JSON.parse(raw.textContent) || [];
        } catch (error) {
            catalog = [];
        }

        var filtered = [];
        var selected = -1;

        function score(item, terms) {
            var title = normalize(item.title);
            var fields = [title, normalize(item.category), normalize(item.desc), normalize(item.badge)].join(' ');
            var total = 0;
            for (var i = 0; i < terms.length; i += 1) {
                if (fields.indexOf(terms[i]) === -1) { return -1; }
                if (title.indexOf(terms[i]) === 0) { total += 6; }
                else if (title.indexOf(terms[i]) > -1) { total += 3; }
                else { total += 1; }
            }
            return total;
        }

        function recentUrls() {
            try {
                return JSON.parse(readStore(STORAGE.recent, '[]')) || [];
            } catch (error) {
                return [];
            }
        }

        function rememberUrl(url) {
            var list = recentUrls().filter(function (entry) { return entry !== url; });
            list.unshift(url);
            writeStore(STORAGE.recent, JSON.stringify(list.slice(0, 6)));
        }

        function render(query) {
            var terms = normalize(query).split(/\s+/).filter(Boolean);

            if (!terms.length) {
                var recent = recentUrls();
                var pinned = catalog.filter(function (item) { return recent.indexOf(item.url) > -1; });
                var rest = catalog.filter(function (item) { return recent.indexOf(item.url) === -1; });
                filtered = pinned.concat(rest).slice(0, 12);
                if (label) {
                    label.textContent = pinned.length
                        ? 'Visitado recientemente + accesos rápidos'
                        : 'Accesos rápidos: rutas, repaso, labs y lecciones';
                }
            } else {
                filtered = catalog
                    .map(function (item) { return { item: item, points: score(item, terms) }; })
                    .filter(function (entry) { return entry.points >= 0; })
                    .sort(function (a, b) { return b.points - a.points; })
                    .slice(0, 16)
                    .map(function (entry) { return entry.item; });
                if (label) { label.textContent = filtered.length + ' resultado(s) para "' + query + '"'; }
            }

            selected = filtered.length ? 0 : -1;

            if (!filtered.length) {
                results.innerHTML = '<div class="search-palette-empty">Sin coincidencias para <strong>"' +
                    escapeHtml(query) + '"</strong>. Prueba con «repaso», «examen», «ruta» o el nombre de un módulo.</div>';
                return;
            }

            results.innerHTML = filtered.map(function (item, index) {
                var desc = item.desc ? ' &bull; ' + escapeHtml(item.desc.substring(0, 72)) + (item.desc.length > 72 ? '…' : '') : '';
                return '<a href="' + item.url + '" class="search-palette-item' + (index === selected ? ' selected' : '') +
                    '" data-index="' + index + '">' +
                    '<div class="search-item-icon">' + (ICONS[item.type] || ICONS.lesson) + '</div>' +
                    '<div class="search-item-content">' +
                        '<div class="search-item-title">' + highlight(item.title, terms) + '</div>' +
                        '<div class="search-item-subtitle">' + escapeHtml(item.category) + desc + '</div>' +
                    '</div>' +
                    '<span class="search-item-badge badge-' + (item.type || 'lesson') + '">' +
                        escapeHtml(item.badge || item.type) + '</span>' +
                '</a>';
            }).join('');

            results.querySelectorAll('.search-palette-item').forEach(function (node) {
                node.addEventListener('mouseenter', function () {
                    select(parseInt(node.getAttribute('data-index'), 10));
                });
                node.addEventListener('click', function () {
                    rememberUrl(node.getAttribute('href'));
                });
            });
        }

        function select(index) {
            selected = index;
            results.querySelectorAll('.search-palette-item').forEach(function (node, i) {
                var active = i === selected;
                node.classList.toggle('selected', active);
                if (active) { node.scrollIntoView({ block: 'nearest' }); }
            });
        }

        function open() {
            palette.style.display = 'block';
            if (box) { box.classList.add('focused'); }
            render(input.value);
        }

        function close() {
            palette.style.display = 'none';
            if (box) { box.classList.remove('focused'); }
            selected = -1;
        }

        input.addEventListener('focus', open);
        input.addEventListener('input', function () { open(); render(input.value); });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (filtered.length) { select(selected < filtered.length - 1 ? selected + 1 : 0); }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (filtered.length) { select(selected > 0 ? selected - 1 : filtered.length - 1); }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (selected >= 0 && filtered[selected]) {
                    rememberUrl(filtered[selected].url);
                    window.location.href = filtered[selected].url;
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close();
                input.blur();
            }
        });

        document.addEventListener('click', function (event) {
            if (box && !box.contains(event.target) && !palette.contains(event.target)) { close(); }
        });

        window.DevLab = window.DevLab || {};
        window.DevLab.openPalette = function () {
            input.focus();
            input.select();
            open();
        };
        window.DevLab.closePalette = close;
    }

    /* ------------------------------------------------------------ Shortcuts */
    function initShortcuts() {
        var help = document.getElementById('shortcuts-overlay');

        function toggleHelp(show) {
            if (!help) { return; }
            help.style.display = show ? 'grid' : 'none';
        }

        document.querySelectorAll('[data-close-overlay]').forEach(function (node) {
            node.addEventListener('click', function () { toggleHelp(false); });
        });
        var helpBtn = document.getElementById('btn-shortcuts');
        if (helpBtn) { helpBtn.addEventListener('click', function () { toggleHelp(true); }); }

        var goPending = false;
        var goTimer = null;
        var GO_TARGETS = {
            d: 'nav-dashboard',
            r: 'nav-roadmap',
            p: 'nav-paths',
            l: 'nav-labs',
            s: 'nav-review',
            e: 'nav-exam'
        };

        window.addEventListener('keydown', function (event) {
            var tag = (document.activeElement && document.activeElement.tagName || '').toLowerCase();
            var typing = tag === 'input' || tag === 'textarea' || tag === 'select' ||
                (document.activeElement && document.activeElement.isContentEditable);
            var ctrl = event.ctrlKey || event.metaKey;
            var key = (event.key || '').toLowerCase();

            if (ctrl && (key === 'k' || key === 'p')) {
                event.preventDefault();
                if (window.DevLab && window.DevLab.openPalette) { window.DevLab.openPalette(); }
                return;
            }
            if (ctrl && key === 'b') {
                event.preventDefault();
                if (window.DevLab && window.DevLab.toggleSidebar) { window.DevLab.toggleSidebar(); }
                return;
            }
            if (event.key === 'Escape') {
                toggleHelp(false);
                if (window.DevLab && window.DevLab.closePalette) { window.DevLab.closePalette(); }
                return;
            }
            if (typing || ctrl || event.altKey) { return; }

            if (key === '/') {
                event.preventDefault();
                if (window.DevLab && window.DevLab.openPalette) { window.DevLab.openPalette(); }
                return;
            }
            if (key === '?') {
                event.preventDefault();
                toggleHelp(help ? help.style.display !== 'grid' : false);
                return;
            }
            if (key === 'g') {
                goPending = true;
                window.clearTimeout(goTimer);
                goTimer = window.setTimeout(function () { goPending = false; }, 1200);
                return;
            }
            if (goPending && GO_TARGETS[key]) {
                goPending = false;
                var link = document.getElementById(GO_TARGETS[key]);
                if (link && link.href) {
                    event.preventDefault();
                    window.location.href = link.href;
                }
            }
        });
    }

    /* --------------------------------------------------------------- Toasts */
    function initToasts() {
        var stack = document.getElementById('toast-stack');
        if (!stack) { return; }

        function dismiss(toast) {
            toast.classList.add('is-leaving');
            window.setTimeout(function () { toast.remove(); }, 300);
        }

        stack.querySelectorAll('.toast').forEach(function (toast, index) {
            toast.style.animationDelay = (index * 90) + 'ms';
            var close = toast.querySelector('.toast-close');
            if (close) { close.addEventListener('click', function () { dismiss(toast); }); }
            window.setTimeout(function () { dismiss(toast); }, 7000 + index * 600);
        });
    }

    function boot() {
        initTheme();
        initSidebar();
        initPalette();
        initShortcuts();
        initToasts();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
