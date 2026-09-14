/* ==========================================================================
   Senior DevLab — Progressive UI enhancements
   Scroll reveal, animated metrics, scrollspy, copy buttons, segmented tabs,
   prediction gates, retrieval counters and the focus timer.
   ========================================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ------------------------------------------------------- Scroll reveal */
    function initReveal() {
        var targets = document.querySelectorAll('.reveal, .stagger');
        if (!targets.length) { return; }

        if (reduceMotion || !('IntersectionObserver' in window)) {
            targets.forEach(function (node) { node.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        targets.forEach(function (node) { observer.observe(node); });
    }

    /* ------------------------------------------ Animated numeric counters */
    function animateCount(node) {
        var target = parseFloat(node.getAttribute('data-count-to'));
        if (isNaN(target)) { return; }
        var suffix = node.getAttribute('data-count-suffix') || '';
        var decimals = parseInt(node.getAttribute('data-count-decimals') || '0', 10);

        if (reduceMotion) {
            node.textContent = target.toFixed(decimals) + suffix;
            return;
        }

        var duration = 900;
        var start = null;

        function frame(timestamp) {
            if (start === null) { start = timestamp; }
            var progress = Math.min((timestamp - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            node.textContent = (target * eased).toFixed(decimals) + suffix;
            if (progress < 1) { window.requestAnimationFrame(frame); }
        }

        window.requestAnimationFrame(frame);
    }

    function initCounters() {
        var nodes = document.querySelectorAll('[data-count-to]');
        if (!nodes.length) { return; }

        if (!('IntersectionObserver' in window)) {
            nodes.forEach(animateCount);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCount(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        nodes.forEach(function (node) { observer.observe(node); });
    }

    /* --------------------------------------- Progress bars and radial rings */
    function initProgress() {
        var bars = document.querySelectorAll('[data-progress]');
        var rings = document.querySelectorAll('[data-ring]');

        function fill() {
            bars.forEach(function (bar) {
                bar.style.width = Math.max(0, Math.min(100, parseFloat(bar.getAttribute('data-progress')) || 0)) + '%';
            });
            rings.forEach(function (ring) {
                ring.style.setProperty('--p', Math.max(0, Math.min(100, parseFloat(ring.getAttribute('data-ring')) || 0)));
            });
        }

        if (reduceMotion) { fill(); return; }
        window.requestAnimationFrame(function () { window.setTimeout(fill, 120); });
    }

    /* ------------------------------------------------ Scrollspy / anchor nav */
    function initScrollspy() {
        var links = document.querySelectorAll('.anchor-chip[href^="#"], .toc-link[href^="#"]');
        if (!links.length) { return; }

        var viewport = document.querySelector('.content-viewport');
        var sections = [];
        links.forEach(function (link) {
            var target = document.getElementById(link.getAttribute('href').substring(1));
            if (target) { sections.push({ link: link, target: target }); }
        });
        if (!sections.length || !viewport) { return; }

        function sync() {
            var offset = viewport.scrollTop + 140;
            var current = sections[0];
            sections.forEach(function (entry) {
                if (entry.target.offsetTop <= offset) { current = entry; }
            });
            sections.forEach(function (entry) {
                entry.link.classList.toggle('is-active', entry === current);
            });
        }

        var queued = false;
        viewport.addEventListener('scroll', function () {
            if (queued) { return; }
            queued = true;
            window.requestAnimationFrame(function () { sync(); queued = false; });
        }, { passive: true });
        sync();
    }

    /* ------------------------------------------------- Reading progress bar */
    function initReadProgress() {
        var bar = document.getElementById('read-progress-bar');
        var viewport = document.querySelector('.content-viewport');
        if (!bar || !viewport) { return; }

        function sync() {
            var max = viewport.scrollHeight - viewport.clientHeight;
            var ratio = max > 0 ? viewport.scrollTop / max : 0;
            bar.style.width = (ratio * 100).toFixed(2) + '%';
        }

        var queued = false;
        viewport.addEventListener('scroll', function () {
            if (queued) { return; }
            queued = true;
            window.requestAnimationFrame(function () { sync(); queued = false; });
        }, { passive: true });
        sync();
    }

    /* ------------------------------------------------------- Copy to clipboard */
    function initCopy() {
        document.querySelectorAll('[data-copy-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var source = document.getElementById(button.getAttribute('data-copy-target'));
                if (!source || !navigator.clipboard) { return; }
                navigator.clipboard.writeText(source.textContent).then(function () {
                    var original = button.textContent;
                    button.textContent = 'Copiado';
                    button.classList.add('is-done');
                    window.setTimeout(function () {
                        button.textContent = original;
                        button.classList.remove('is-done');
                    }, 1600);
                });
            });
        });
    }

    /* ---------------------------------------------------- Segmented controls */
    function initSegmented() {
        document.querySelectorAll('[data-segmented]').forEach(function (group) {
            var thumb = group.querySelector('.segmented-thumb');
            var buttons = group.querySelectorAll('[data-segment]');

            function moveThumb(button) {
                if (!thumb || !button) { return; }
                thumb.style.width = button.offsetWidth + 'px';
                thumb.style.transform = 'translateX(' + (button.offsetLeft - group.querySelector('[data-segment]').offsetLeft) + 'px)';
            }

            function activate(button) {
                buttons.forEach(function (other) { other.classList.toggle('is-active', other === button); });
                var panelName = button.getAttribute('data-segment');
                group.parentElement.querySelectorAll('[data-panel]').forEach(function (panel) {
                    var match = panel.getAttribute('data-panel') === panelName;
                    panel.hidden = !match;
                    if (match) { panel.classList.add('anim-fade-in'); }
                });
                moveThumb(button);
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    activate(button);
                });
            });

            var initial = group.querySelector('[data-segment].is-active') || buttons[0];
            if (initial) { activate(initial); }
            window.addEventListener('resize', function () {
                moveThumb(group.querySelector('[data-segment].is-active'));
            });
        });
    }

    /* ------------------------------------------------------ Prediction gates */
    function initPredictGates() {
        document.querySelectorAll('.predict-gate').forEach(function (gate) {
            var button = gate.querySelector('[data-reveal-gate]');
            if (!button) { return; }
            button.addEventListener('click', function () {
                gate.classList.add('is-open');
            });
        });
    }

    /* ---------------------------------------------------- Retrieval counters */
    function initRetrieval() {
        document.querySelectorAll('[data-retrieval]').forEach(function (wrapper) {
            var field = wrapper.querySelector('textarea');
            var counter = wrapper.querySelector('.retrieval-count');
            var submit = wrapper.querySelector('[data-retrieval-submit]');
            if (!field) { return; }

            var minimum = parseInt(wrapper.getAttribute('data-retrieval') || '120', 10);

            function sync() {
                var length = field.value.trim().length;
                if (counter) {
                    counter.textContent = length + ' / ' + minimum + ' caracteres mínimos para una explicación útil';
                    counter.style.color = length >= minimum ? 'var(--accent-success)' : 'var(--text-muted)';
                }
                if (submit) { submit.disabled = length < 20; }
            }

            field.addEventListener('input', sync);
            sync();
        });
    }

    /* ----------------------------------------------------------- Focus timer */
    function initFocusTimer() {
        var widget = document.getElementById('focus-widget');
        if (!widget) { return; }

        var clock = widget.querySelector('.focus-clock');
        var toggle = widget.querySelector('[data-focus-toggle]');
        var reset = widget.querySelector('[data-focus-reset]');
        var DEFAULT_SECONDS = 25 * 60;
        var remaining = DEFAULT_SECONDS;
        var timer = null;

        function paint() {
            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            if (clock) {
                clock.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
        }

        function stop() {
            window.clearInterval(timer);
            timer = null;
            widget.classList.remove('is-running');
            if (toggle) { toggle.textContent = 'Iniciar'; }
        }

        function tick() {
            remaining -= 1;
            if (remaining <= 0) {
                remaining = 0;
                paint();
                stop();
                if (window.DevLab && window.DevLab.notify) {
                    window.DevLab.notify('Bloque de enfoque completado. Toma 5 minutos y vuelve al repaso.', 'success');
                }
                return;
            }
            paint();
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                if (timer) { stop(); return; }
                timer = window.setInterval(tick, 1000);
                widget.classList.add('is-running');
                toggle.textContent = 'Pausar';
            });
        }
        if (reset) {
            reset.addEventListener('click', function () {
                stop();
                remaining = DEFAULT_SECONDS;
                paint();
            });
        }

        paint();
    }

    /* --------------------------------------------------------- Toast factory */
    window.DevLab = window.DevLab || {};
    window.DevLab.notify = function (message, tone) {
        var stack = document.getElementById('toast-stack');
        if (!stack) { return; }
        var toast = document.createElement('div');
        toast.className = 'toast is-' + (tone || 'info');
        toast.innerHTML = '<span></span><button type="button" class="toast-close" aria-label="Cerrar">&times;</button>';
        toast.querySelector('span').textContent = message;
        toast.querySelector('.toast-close').addEventListener('click', function () { toast.remove(); });
        stack.appendChild(toast);
        window.setTimeout(function () {
            toast.classList.add('is-leaving');
            window.setTimeout(function () { toast.remove(); }, 300);
        }, 6000);
    };

    function boot() {
        initReveal();
        initCounters();
        initProgress();
        initScrollspy();
        initReadProgress();
        initCopy();
        initSegmented();
        initPredictGates();
        initRetrieval();
        initFocusTimer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
