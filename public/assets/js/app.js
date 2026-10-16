/**
 * eMunicipal — Application JavaScript
 * Vanilla JS only. No external frameworks.
 * Requires Bootstrap 5 loaded first.
 */

'use strict';

// ============================================================
// Sidebar / mobile navigation drawer (authenticated layout)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('appSidebar');
    const overlay       = document.getElementById('sidebarOverlay');

    // ── helpers ──────────────────────────────────────────────
    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('sidebar-open');
        if (overlay) overlay.classList.add('active');
        document.body.classList.add('mobile-nav-open');
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
        // Move focus into the sidebar for keyboard accessibility
        var firstLink = sidebar.querySelector('a.nav-link, button.nav-link');
        if (firstLink) firstLink.focus();
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('sidebar-open');
        if (overlay) overlay.classList.remove('active');
        document.body.classList.remove('mobile-nav-open');
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
    }

    // ── hamburger button ─────────────────────────────────────
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            if (sidebar.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    // ── backdrop click ───────────────────────────────────────
    if (overlay) {
        overlay.addEventListener('click', function () {
            closeSidebar();
        });
    }

    // ── Escape key ───────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
            if (sidebarToggle) sidebarToggle.focus(); // return focus to trigger
        }
    });

    // ── Close drawer when a nav link is clicked (mobile only) ─
    // On desktop the sidebar is always visible so we skip this.
    if (sidebar) {
        sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                // Only auto-close when sidebar is in drawer mode (mobile)
                if (sidebar.classList.contains('sidebar-open')) {
                    closeSidebar();
                }
            });
        });
    }

    // ============================================================
    // Logout confirmation modal
    // ============================================================
    const logoutConfirmBtn = document.getElementById('logoutConfirmBtn');
    const logoutForm = document.getElementById('logoutForm');
    if (logoutConfirmBtn && logoutForm) {
        logoutConfirmBtn.addEventListener('click', function () {
            logoutForm.submit();
        });
    }

    // ============================================================
    // Password show/hide toggle (login and register pages)
    // ============================================================
    document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.pwToggle;
            const input    = document.getElementById(targetId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.querySelector('i').className = 'bi bi-eye-slash';
                this.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                this.querySelector('i').className = 'bi bi-eye';
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });

    // ============================================================
    // Auto-dismiss flash alerts after 5 seconds
    // ============================================================
    document.querySelectorAll('.alert-flash').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ============================================================
    // Confirm-before-submit for dangerous forms (data-confirm)
    // ============================================================
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ============================================================
    // File upload: show filenames as a block list (Req 5.6)
    // ============================================================
    document.querySelectorAll('input[type="file"][data-file-label]').forEach(function (input) {
        input.addEventListener('change', function () {
            var labelEl = document.getElementById(this.dataset.fileLabel);
            if (!labelEl) return;
            if (!this.files || this.files.length === 0) {
                labelEl.textContent = 'No files chosen';
                return;
            }
            labelEl.innerHTML = Array.from(this.files).map(function (f) {
                return '<span class="d-block text-muted-sm">'
                    + '<i class="bi bi-file-earmark me-1" aria-hidden="true"></i>'
                    + f.name + '</span>';
            }).join('');
        });
    });

    // ============================================================
    // Double-submit prevention (Req 16.1)
    // Applied automatically to all forms with data-loading attribute
    // ============================================================
    function enableDoubleSubmitPrevention(form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('[type="submit"]');
            if (!btn || btn.disabled) return;
            var loadingLabel = form.dataset.loading || 'Processing…';
            btn.disabled = true;
            btn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" '
                + 'role="status" aria-hidden="true"></span>'
                + loadingLabel;
        }, { once: false });
    }

    document.querySelectorAll('form[data-loading]').forEach(function (form) {
        enableDoubleSubmitPrevention(form);
    });

    // ============================================================
    // ── LIVE PASSWORD VALIDATION (register page) ──────────────
    // ============================================================

    /**
     * Update a single checklist item (by element ID).
     * @param {string} id    — id of the <li> element
     * @param {boolean} valid
     */
    function updateChecklistItem(id, valid) {
        var item = document.getElementById(id);
        if (!item) return;
        var icon = item.querySelector('i');
        if (valid) {
            item.classList.remove('pwc-invalid');
            item.classList.add('pwc-valid');
            if (icon) icon.className = 'bi bi-check-circle-fill';
        } else {
            item.classList.remove('pwc-valid');
            item.classList.add('pwc-invalid');
            if (icon) icon.className = 'bi bi-x-circle-fill';
        }
    }

    /**
     * updatePasswordChecklist — updates all four criteria items.
     * Matches Validator::passwordStrength() rules exactly:
     *   length >= 8, uppercase letter, lowercase letter, digit.
     * @param {string} password
     */
    function updatePasswordChecklist(password) {
        updateChecklistItem('pwc-length', password.length >= 8);
        updateChecklistItem('pwc-upper',  /[A-Z]/.test(password));
        updateChecklistItem('pwc-lower',  /[a-z]/.test(password));
        updateChecklistItem('pwc-number', /[0-9]/.test(password));
    }

    /**
     * allChecklistItemsValid — returns true only if all four criteria pass.
     */
    function allChecklistItemsValid() {
        var ids = ['pwc-length', 'pwc-upper', 'pwc-lower', 'pwc-number'];
        return ids.every(function (id) {
            var el = document.getElementById(id);
            return el && el.classList.contains('pwc-valid');
        });
    }

    /**
     * updateConfirmMatch — updates the confirm password match indicator.
     * @param {string} password
     * @param {string} confirm
     */
    function updateConfirmMatch(password, confirm) {
        var msg = document.getElementById('pwMatchMsg');
        if (!msg) return;
        if (confirm === '') {
            msg.innerHTML = '';
            return;
        }
        if (password === confirm) {
            msg.innerHTML =
                '<i class="bi bi-check-circle-fill text-success me-1" aria-hidden="true"></i>'
                + '<span class="text-success">Passwords match</span>';
        } else {
            msg.innerHTML =
                '<i class="bi bi-x-circle-fill text-danger me-1" aria-hidden="true"></i>'
                + '<span class="text-danger">Passwords do not match</span>';
        }
    }

    // Bind live validation to register page fields
    var pwInput        = document.getElementById('password');
    var pwConfirmInput = document.getElementById('password_confirmation');
    var registerForm   = document.getElementById('registerForm');

    if (registerForm && pwInput) {
        pwInput.addEventListener('input', function () {
            updatePasswordChecklist(this.value);
            if (pwConfirmInput) {
                updateConfirmMatch(this.value, pwConfirmInput.value);
            }
        });
    }
    if (registerForm && pwConfirmInput) {
        pwConfirmInput.addEventListener('input', function () {
            if (pwInput) {
                updateConfirmMatch(pwInput.value, this.value);
            }
        });
    }

    // ============================================================
    // ── AJAX SUCCESS URL PREDICATES ───────────────────────────
    // ============================================================

    /**
     * isLoginSuccess — true when the response URL is NOT a login page.
     * @param {string} responseUrl
     * @returns {boolean}
     */
    function isLoginSuccess(responseUrl) {
        try {
            var url = new URL(responseUrl);
            // Success = redirected AWAY from /login
            return !/\/login(\/|$|\?|#|$)/.test(url.pathname);
        } catch (e) {
            return false;
        }
    }

    /**
     * isRegisterSuccess — true when the response URL is a login page.
     * @param {string} responseUrl
     * @returns {boolean}
     */
    function isRegisterSuccess(responseUrl) {
        try {
            var url = new URL(responseUrl);
            // Success = redirected TO /login
            return /\/login(\/|$|\?|#|$)/.test(url.pathname);
        } catch (e) {
            return false;
        }
    }

    // ============================================================
    // ── AJAX FORM UTILITIES ───────────────────────────────────
    // ============================================================

    function setLoading(btn, label) {
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>'
            + label;
    }

    function resetLoading(btn, label) {
        if (!btn) return;
        btn.disabled = false;
        btn.textContent = label;
    }

    function showFormError(containerId, message) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML =
            '<div class="alert alert-danger d-flex align-items-start gap-2 py-2 px-3 mb-3" role="alert">'
            + '<i class="bi bi-exclamation-triangle flex-shrink-0 mt-1" aria-hidden="true"></i>'
            + '<span>' + message + '</span>'
            + '</div>';
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearFormError(containerId) {
        var container = document.getElementById(containerId);
        if (container) container.innerHTML = '';
    }

    /**
     * extractAlertText — parse an alert-danger message from a server-rendered HTML string.
     */
    function extractAlertText(html) {
        try {
            var doc   = new DOMParser().parseFromString(html, 'text/html');
            var alert = doc.querySelector('.alert-danger span, .alert-danger');
            if (alert) return alert.textContent.trim();
        } catch (e) { /* ignore */ }
        return 'Something went wrong. Please try again.';
    }

    // ============================================================
    // ── LOGIN AJAX HANDLER ────────────────────────────────────
    // ============================================================
    var loginFormEl    = document.getElementById('loginForm');
    var loginSubmitBtn = document.getElementById('loginSubmitBtn');

    if (loginFormEl && loginSubmitBtn) {
        loginFormEl.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearFormError('loginErrorContainer');
            setLoading(loginSubmitBtn, 'Signing in...');

            try {
                var resp = await fetch(loginFormEl.action, {
                    method:      'POST',
                    body:        new FormData(loginFormEl),
                    redirect:    'follow',
                    credentials: 'same-origin'
                });

                if (isLoginSuccess(resp.url)) {
                    window.location.href = resp.url;
                } else {
                    var html      = await resp.text();
                    var errorText = extractAlertText(html);
                    showFormError('loginErrorContainer', errorText);
                    resetLoading(loginSubmitBtn, 'Sign In');
                }
            } catch (err) {
                showFormError('loginErrorContainer', 'Network error. Please check your connection and try again.');
                resetLoading(loginSubmitBtn, 'Sign In');
            }
        });
    }

    // ============================================================
    // ── REGISTER AJAX HANDLER ─────────────────────────────────
    // ============================================================
    var registerFormEl    = document.getElementById('registerForm');
    var registerSubmitBtn = document.getElementById('registerSubmitBtn');

    if (registerFormEl && registerSubmitBtn) {
        registerFormEl.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearFormError('registerErrorContainer');

            // Client-side gate: password checklist must all pass
            if (document.getElementById('pwChecklist') && !allChecklistItemsValid()) {
                showFormError('registerErrorContainer', 'Please meet all password requirements before submitting.');
                return;
            }

            setLoading(registerSubmitBtn, 'Creating account...');

            try {
                var resp = await fetch(registerFormEl.action, {
                    method:      'POST',
                    body:        new FormData(registerFormEl),
                    redirect:    'follow',
                    credentials: 'same-origin'
                });

                if (isRegisterSuccess(resp.url)) {
                    window.location.href = resp.url;
                } else {
                    var html      = await resp.text();
                    var errorText = extractAlertText(html);
                    showFormError('registerErrorContainer', errorText);
                    resetLoading(registerSubmitBtn, 'Create Account');
                }
            } catch (err) {
                showFormError('registerErrorContainer', 'Network error. Please check your connection and try again.');
                resetLoading(registerSubmitBtn, 'Create Account');
            }
        });
    }

    // ============================================================
    // Public landing page — smooth scroll + scroll-spy + enter animation
    // ONE unified implementation; authenticated page transition is separate.
    // ============================================================
    (function () {
        var pubNav = document.getElementById('publicNav');
        if (!pubNav) return; // only runs on the public landing page

        var navbar         = document.querySelector('.navbar.sticky-top');
        var reducedMotion  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // ── 1. LANDING PAGE ENTER ANIMATION ──────────────────────
        // CSS gives #main-content.lp-content its start state (opacity:0, translateY(20px))
        // before JS runs. JS adds class "lp-entered" to #main-content after 20ms to
        // trigger the transition. The navbar is NOT animated — it sits outside #main-content.
        var mainContent = document.getElementById('main-content');
        if (mainContent) {
            // lp-content is now in the server-rendered HTML (public.php), so the initial
            // hidden state is painted before JS runs. No need to add it here.
            if (!reducedMotion) {
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        mainContent.classList.add('lp-entered');
                    });
                });
            } else {
                mainContent.classList.add('lp-entered');
            }
        }

        // ── 2. NAVBAR SCROLL SHADOW ───────────────────────────────
        if (navbar) {
            var onNavScroll = function () {
                if (window.scrollY > 10) {
                    navbar.classList.add('navbar-scrolled');
                } else {
                    navbar.classList.remove('navbar-scrolled');
                }
            };
            window.addEventListener('scroll', onNavScroll, { passive: true });
            onNavScroll(); // run once on load
        }

        // ── 3. BUILD SECTION MAP ──────────────────────────────────
        var anchorLinks = Array.prototype.slice.call(
            pubNav.querySelectorAll('a.nav-link[href^="#"]')
        );
        if (!anchorLinks.length) return;

        var sections = [];
        anchorLinks.forEach(function (link) {
            var id = link.getAttribute('href').replace('#', '');
            var el = document.getElementById(id);
            if (el) sections.push({ id: id, el: el, link: link });
        });

        // ── 4. HELPERS ────────────────────────────────────────────
        function getNavbarHeight() {
            return navbar ? navbar.offsetHeight : 0;
        }

        // Set active link (and aria-current)
        function setActive(id) {
            anchorLinks.forEach(function (l) {
                l.classList.remove('active');
                l.removeAttribute('aria-current');
            });
            sections.forEach(function (s) {
                if (s.id === id) {
                    s.link.classList.add('active');
                    s.link.setAttribute('aria-current', 'page');
                }
            });
        }

        // ── 5. SMOOTH SCROLL ON CLICK ─────────────────────────────
        // We intercept clicks ourselves and use window.scrollTo for
        // reliable cross-browser smooth scrolling with navbar offset.
        var scrollLocked = false;   // prevents IO from overriding click-active during scroll
        var scrollLockTimer = null;

        anchorLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                var id = this.getAttribute('href').replace('#', '');
                var target = document.getElementById(id);
                if (!target) return;

                e.preventDefault();

                // Close mobile nav if open
                var collapse = document.getElementById('publicNav');
                if (collapse && collapse.classList.contains('show')) {
                    var bsCollapse = bootstrap.Collapse.getInstance(collapse);
                    if (bsCollapse) bsCollapse.hide();
                }

                // Update active immediately on click
                setActive(id);

                // Lock IO from overriding active state during scroll (750ms)
                scrollLocked = true;
                clearTimeout(scrollLockTimer);
                scrollLockTimer = setTimeout(function () { scrollLocked = false; }, 750);

                if (reducedMotion) {
                    // No animation — jump directly
                    var topPos = id === 'home' ? 0 : target.getBoundingClientRect().top + window.scrollY - getNavbarHeight();
                    window.scrollTo(0, Math.max(0, topPos));
                    return;
                }

                // Smooth scroll: for #home always go to very top (no offset gap)
                if (id === 'home') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    var rect    = target.getBoundingClientRect();
                    var scrollY = window.scrollY || window.pageYOffset;
                    var targetY = rect.top + scrollY - getNavbarHeight() - 4; // 4px breathing room
                    window.scrollTo({ top: Math.max(0, targetY), behavior: 'smooth' });
                }
            });
        });

        // ── 6. SCROLL-SPY (IntersectionObserver) ─────────────────
        // Only updates active state when NOT locked by a click.
        if ('IntersectionObserver' in window) {
            var ioMargin = '-' + (getNavbarHeight() + 12) + 'px 0px -60% 0px';
            var io = new IntersectionObserver(function (entries) {
                if (scrollLocked) return; // click just fired — wait
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        setActive(entry.target.id);
                    }
                });
            }, { rootMargin: ioMargin, threshold: 0 });

            sections.forEach(function (s) { io.observe(s.el); });
        } else {
            // Scroll-event fallback for old browsers
            var ticking = false;
            window.addEventListener('scroll', function () {
                if (ticking || scrollLocked) return;
                ticking = true;
                requestAnimationFrame(function () {
                    var offset  = getNavbarHeight() + 12;
                    var current = sections[0] ? sections[0].id : '';
                    sections.forEach(function (s) {
                        if (s.el.getBoundingClientRect().top <= offset) current = s.id;
                    });
                    setActive(current);
                    ticking = false;
                });
            }, { passive: true });
        }
    })();

    // ============================================================
    // Authenticated pages — fade + slide transition on navigation
    // Applies to Resident, Staff, and Admin (all share auth.php).
    // ============================================================
    (function () {
        if (!document.getElementById('appSidebar')) return; // authenticated only

        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var sidebarEl = document.getElementById('appSidebar');
        var overlayEl = document.getElementById('sidebarOverlay');

        // ── ENTER: fade + slide up on page load ──────────────────
        // CSS sets body.auth-body to opacity:0 and .auth-content to translateY(Npx)
        // before JS runs, so the browser paints the start state on first render.
        // We only add 'page-entered' after a guaranteed paint frame to trigger
        // the CSS transition from the already-painted start state to the final state.
        if (!reducedMotion) {
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    document.body.classList.add('page-entered');
                });
            });
        } else {
            document.body.classList.add('page-entered');
        }

        // ── LEAVE: intercept sidebar nav links ───────────────────
        // Use event delegation on the sidebar so it works regardless
        // of how many links are present or how the DOM is structured.
        document.addEventListener('click', function (e) {
            // Find the closest <a> ancestor that is a sidebar nav link
            var link = e.target.closest('.sidebar-nav a.nav-link, .sidebar-brand');
            if (!link) return;

            var href = link.getAttribute('href');

            // Skip: no href, anchors, javascript:, target="_blank", disabled
            if (!href) return;
            if (href.charAt(0) === '#') return;
            if (href.toLowerCase().indexOf('javascript') === 0) return;
            if (link.getAttribute('target') === '_blank') return;
            if (link.hasAttribute('disabled') || link.classList.contains('disabled')) return;

            e.preventDefault();
            var dest = href;

            // Close mobile sidebar immediately (no visual wait needed)
            if (sidebarEl && sidebarEl.classList.contains('sidebar-open')) {
                sidebarEl.classList.remove('sidebar-open');
                if (overlayEl) overlayEl.classList.remove('active');
            }

            // LEAVE ANIMATION — content-only, inline style approach:
            // 1. Remove page-entered so the reduced-motion entry CSS no longer applies.
            // 2. Animate only .auth-content (opacity + slide) — body/sidebar are untouched.
            // 3. Navigate after the animation duration + small buffer.
            document.body.classList.remove('page-entered');
            var authContent = document.querySelector('.auth-content');

            if (authContent) {
                // Apply leave animation directly to content — NOT to body
                authContent.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                authContent.style.opacity    = '0';
                authContent.style.transform  = 'translateY(-8px)';
            }

            setTimeout(function () {
                window.location.href = dest;
            }, 230);
        });
    })();

    // ============================================================
    // FAQ hover list — tap/click toggle for mobile
    // (CSS handles hover on desktop; JS handles click on touch)
    // ============================================================
    document.querySelectorAll('.faq-hover-item').forEach(function (item) {
        // Keyboard accessibility: Enter/Space toggles open state
        function toggleFaq() {
            var isOpen = item.classList.contains('faq-open');
            // Close all others
            document.querySelectorAll('.faq-hover-item.faq-open').forEach(function (other) {
                if (other !== item) {
                    other.classList.remove('faq-open');
                    other.setAttribute('aria-expanded', 'false');
                }
            });
            item.classList.toggle('faq-open', !isOpen);
            item.setAttribute('aria-expanded', String(!isOpen));
        }

        item.addEventListener('click', toggleFaq);
        item.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleFaq();
            }
        });
    });

    // ============================================================
    // Contact form — client-side validation + fetch AJAX
    // ============================================================
    var contactForm = document.getElementById('contactForm');
    var contactSubmitBtn = document.getElementById('contactSubmitBtn');
    var contactSuccessMsg = document.getElementById('contactSuccessMsg');
    var contactErrorMsg   = document.getElementById('contactErrorMsg');

    if (contactForm && contactSubmitBtn) {
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Hide previous messages
            if (contactSuccessMsg) contactSuccessMsg.style.display = 'none';
            if (contactErrorMsg)   contactErrorMsg.style.display   = 'none';

            // Bootstrap validation
            contactForm.classList.add('was-validated');
            if (!contactForm.checkValidity()) return;

            // Loading state
            contactSubmitBtn.disabled = true;
            contactSubmitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending...';

            try {
                var appBase = document.querySelector('meta[name="app-url"]')
                    ? document.querySelector('meta[name="app-url"]').content
                    : window.location.origin + window.location.pathname.replace(/\/$/, '');
                // Determine app URL from existing script src pattern
                var scriptSrc = document.querySelector('script[src*="app.js"]');
                var appUrl = '';
                if (scriptSrc) {
                     // Strip query string (e.g. ?v=3.3) before replacing the path,
                    // otherwise the cache-buster ends up inside the URL and breaks the fetch.
                    var srcWithoutQuery = scriptSrc.src.split('?')[0];
                    appUrl = srcWithoutQuery.replace('/public/assets/js/app.js', '');
                }

                var resp = await fetch(appUrl + '/contact', {
                    method: 'POST',
                    body: new FormData(contactForm),
                    credentials: 'same-origin',
                    redirect: 'follow'
                });

                var data = await resp.json();

                if (data.success) {
                    contactForm.reset();
                    contactForm.classList.remove('was-validated');
                    if (contactSuccessMsg) contactSuccessMsg.style.display = 'block';
                } else {
                    if (contactErrorMsg) contactErrorMsg.style.display = 'block';
                }
            } catch (err) {
                if (contactErrorMsg) contactErrorMsg.style.display = 'block';
            } finally {
                contactSubmitBtn.disabled = false;
                contactSubmitBtn.innerHTML = '<i class="bi bi-send me-2" aria-hidden="true"></i>Send Message';
            }
        });
    }

});


    // ============================================================
    // Landing Page — Scroll Reveal + Progress Indicator
    // ============================================================
    (function () {
        if (!document.getElementById('publicNav')) return; // public landing only

        var revealElements = [];

        // ── 1. SCROLL REVEAL SETUP ────────────────────────────────
        function initScrollReveal() {
            // Hero section
            var heroSection = document.querySelector('#home.lp-ref-hero');
            if (heroSection) {
                var heroLeft  = heroSection.querySelector('.col-12.col-lg-6:first-child');
                var heroRight = heroSection.querySelector('.col-12.col-lg-6:last-child');
                if (heroLeft)  { heroLeft.classList.add('scroll-reveal-left');  revealElements.push(heroLeft);  }
                if (heroRight) { heroRight.classList.add('scroll-reveal-right'); revealElements.push(heroRight); }
            }

            // Track Request section — cards already have scroll-reveal classes
            var trackFormCard = document.querySelector('.track-form-card');
            var trackResultCard = document.querySelector('.track-result-card');
            if (trackFormCard) revealElements.push(trackFormCard);
            if (trackResultCard) revealElements.push(trackResultCard);

            // Feature cards
            document.querySelectorAll('.lp-ref-feature-card').forEach(function(card, index) {
                card.classList.add('scroll-reveal', 'scroll-reveal-delay-' + (Math.min(index + 1, 5)));
                revealElements.push(card);
            });

            // About section
            var aboutSection = document.querySelector('#about');
            if (aboutSection) {
                var aboutLeft  = aboutSection.querySelector('.col-12.col-lg-6:first-child');
                var aboutRight = aboutSection.querySelector('.col-12.col-lg-6:last-child');
                if (aboutLeft)  { aboutLeft.classList.add('scroll-reveal-left');  revealElements.push(aboutLeft);  }
                if (aboutRight) { aboutRight.classList.add('scroll-reveal-right'); revealElements.push(aboutRight); }
            }

            // How It Works steps
            document.querySelectorAll('#how-it-works .lp-ref-step-item').forEach(function(step, index) {
                step.classList.add('scroll-reveal', 'scroll-reveal-delay-' + (Math.min(index % 3, 3) + 1));
                revealElements.push(step);
            });

            // Services cards
            document.querySelectorAll('#services .row.g-3 > div[class*="col-"]').forEach(function(col, index) {
                var card = col.querySelector('div[style*="background:#fff"]');
                if (card) {
                    card.classList.add('scroll-reveal-subtle', 'scroll-reveal-delay-' + (Math.min(index % 4, 4) + 1));
                    revealElements.push(card);
                }
            });

            // Contact section
            var contactSection = document.querySelector('#contact');
            if (contactSection) {
                var contactForm = contactSection.querySelector('form');
                if (contactForm) {
                    contactForm.classList.add('scroll-reveal-left');
                    revealElements.push(contactForm);
                }
            }
        }

        // ── 2. INTERSECTION OBSERVER ──────────────────────────────
        function createScrollObserver() {
            if (!('IntersectionObserver' in window)) {
                revealElements.forEach(function(el) { el.classList.add('revealed'); });
                return;
            }

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, { root: null, rootMargin: '0px 0px -10% 0px', threshold: 0.15 });

            revealElements.forEach(function(el) { observer.observe(el); });
        }

        // ── 3. SCROLL PROGRESS BAR ────────────────────────────────
        var progressBar = document.createElement('div');
        progressBar.className = 'scroll-progress';
        document.body.appendChild(progressBar);

        function updateScrollProgress() {
            var maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            var progress  = maxScroll > 0 ? (window.scrollY / maxScroll) * 100 : 0;
            progressBar.style.width = Math.min(100, Math.max(0, progress)) + '%';
            if (window.scrollY > 200) {
                progressBar.classList.add('visible');
            } else {
                progressBar.classList.remove('visible');
            }
        }

        // ── 4. INITIALIZE ─────────────────────────────────────────
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initScrollReveal();
                createScrollObserver();
            });
        } else {
            initScrollReveal();
            createScrollObserver();
        }

        window.addEventListener('scroll', updateScrollProgress, { passive: true });
        updateScrollProgress();

    })();

    // ============================================================
    // Landing Page Reference: FAQ Accordion
    // ============================================================
    document.querySelectorAll('.lp-ref-faq-item').forEach(function(item) {
        var question = item.querySelector('.lp-ref-faq-question');
        if (!question) return;

        question.addEventListener('click', function() {
            document.querySelectorAll('.lp-ref-faq-item').forEach(function(otherItem) {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            item.classList.toggle('active');
        });
    });
