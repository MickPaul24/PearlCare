/* scripts.js — shared client-side behaviors */

// Override native alert to use theme-aware notifications
window.alert = function(message) {
    try {
        showNotification('info', String(message));
    } catch (e) {
        // Fallback to default if our helpers aren't available yet
        if (typeof window.console !== 'undefined') console.log('alert:', message);
    }
};

// Basic modal open/close helpers (ensure available before modals are used)
function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('open');
}
function setCookie(name, value, days) {
    var expires = '';
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
}

function getCookie(name) {
    var pairs = document.cookie.split('; ');
    for (var i = 0; i < pairs.length; i++) {
        var parts = pairs[i].split('=');
        if (parts[0] === name) {
            return decodeURIComponent(parts[1] || '');
        }
    }
    return '';
}

function initThemeToggle() {
    var button = document.getElementById('themeToggle');
    if (!button) return;

    button.addEventListener('click', function () {
        var html = document.documentElement;
        var current = html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        setCookie('theme', next, 365);
        button.textContent = next === 'dark' ? '☀️' : '🌙';
    });
}

function getNotificationContainer() {
    var container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
    return container;
}

function showNotification(type, message, duration) {
    duration = typeof duration === 'number' ? duration : 4500;
    var container = getNotificationContainer();
    var notification = document.createElement('div');
    notification.className = 'notification notification-' + (type || 'info');
    notification.innerHTML = '<div class="notification-body">' + message + '</div>' +
        '<button type="button" class="notification-close" aria-label="Close notification">×</button>';

    var closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', function () {
        notification.classList.remove('visible');
        notification.addEventListener('transitionend', function () { notification.remove(); }, { once: true });
    });

    container.appendChild(notification);
    requestAnimationFrame(function () {
        notification.classList.add('visible');
    });
    setTimeout(function () {
        notification.classList.remove('visible');
        notification.addEventListener('transitionend', function () { notification.remove(); }, { once: true });
    }, duration);
}

// ---- Confirm / Prompt helpers -------------------------------
var _confirmDefaults = {
    header: 'linear-gradient(135deg,#475569 0%,#334155 100%)',
    icon: 'alert-circle',
    okClass: 'btn btn-primary',
    okLabel: 'OK'
};

function showConfirm(message, title, callback, opts) {
    if (typeof title === 'function') { opts = callback; callback = title; title = 'Confirm'; }
    opts = opts || {};

    var header  = document.getElementById('confirmHeader');
    var iconEl  = document.getElementById('confirmIcon');
    var titleEl = document.getElementById('confirmTitle');
    var body    = document.getElementById('confirmBody');
    var ok      = document.getElementById('confirmOk');
    var cancel  = document.getElementById('confirmCancel');

    // Apply styling
    var isDanger = opts.type === 'danger';
    header.style.background  = isDanger ? 'linear-gradient(135deg,#dc2626 0%,#b91c1c 100%)' : _confirmDefaults.header;
    iconEl.setAttribute('data-lucide', isDanger ? (opts.icon || 'trash-2') : _confirmDefaults.icon);
    if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [iconEl] });
    titleEl.textContent = title || 'Confirm';
    body.textContent    = message || '';
    ok.className        = isDanger ? 'btn btn-danger' : _confirmDefaults.okClass;
    ok.style.background = isDanger ? 'var(--danger,#dc2626)' : '';
    ok.style.color      = isDanger ? '#fff' : '';
    ok.style.border     = isDanger ? 'none' : '';
    ok.textContent      = opts.okLabel || (isDanger ? 'Delete' : _confirmDefaults.okLabel);

    openModal('confirmModal');

    function cleanup() {
        ok.removeEventListener('click', onOk);
        cancel.removeEventListener('click', onCancel);
        document.querySelector('[data-close="confirmModal"]').removeEventListener('click', onCancel);
    }
    function onOk()     { closeModal('confirmModal'); cleanup(); if (callback) callback(true); }
    function onCancel() { closeModal('confirmModal'); cleanup(); if (callback) callback(false); }
    ok.addEventListener('click', onOk);
    cancel.addEventListener('click', onCancel);
    document.querySelector('[data-close="confirmModal"]').addEventListener('click', onCancel);
}

function showPrompt(message, defaultValue, title, callback) {
    if (typeof title === 'function') { callback = title; title = 'Input'; }
    var modal = document.getElementById('promptModal');
    var body = document.getElementById('promptBody');
    var input = document.getElementById('promptInput');
    var ok = document.getElementById('promptOk');
    var cancel = document.getElementById('promptCancel');
    document.getElementById('promptTitle').textContent = title || 'Input';
    body.textContent = message || '';
    input.value = defaultValue || '';
    openModal('promptModal');
    function cleanup() {
        ok.removeEventListener('click', onOk);
        cancel.removeEventListener('click', onCancel);
    }
    function onOk() { var val = input.value; closeModal('promptModal'); cleanup(); if (callback) callback(val); }
    function onCancel() { closeModal('promptModal'); cleanup(); if (callback) callback(null); }
    ok.addEventListener('click', onOk);
    cancel.addEventListener('click', onCancel);
}

function initModals() {
    document.querySelectorAll('[data-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.dataset.open);
            if (target) {
                // Handle edit user modal
                if (button.dataset.open === 'editUserModal') {
                    document.getElementById('edit_user_id').value = button.dataset.userId;
                    document.getElementById('edit_full_name').value = button.dataset.userName;
                    document.getElementById('edit_email').value = button.dataset.userEmail;
                    document.getElementById('edit_role').value = button.dataset.userRole;
                    document.getElementById('edit_is_active').checked = button.dataset.userActive === '1';
                }
                target.classList.add('open');
            }
        });
    });
    document.querySelectorAll('[data-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.dataset.close);
            if (target) target.classList.remove('open');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                overlay.classList.remove('open');
            }
        });
    });
}

function confirmAction(message, callback) {
    showConfirm(message, function(ok) {
        if (ok) callback();
    });
}

// Returns the MVC AJAX endpoint — relies on window.BASE_URL injected by the layout
function ajaxUrl() {
    return (window.BASE_URL || '') + '/ajax';
}

function deletePatient(patientId) {
    confirmAction('Are you sure you want to delete this patient record? This cannot be undone.', function() {
        ajaxRequest({
            url: ajaxUrl(),
            method: 'POST',
            data: { action: 'patient_delete', id: patientId },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    showNotification('error', response.message || 'Could not delete the record.');
                }
            },
            error: function() {
                showNotification('error', 'Unable to reach the server.');
            }
        });
    });
}

function ajaxRequest(options) {
    var xhr = new XMLHttpRequest();
    xhr.open(options.method || 'POST', options.url || ajaxUrl(), true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (!(options.data instanceof FormData)) {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    }
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            var response = {};
            try {
                response = JSON.parse(xhr.responseText || '{}');
            } catch (e) {
                response = { success: false, message: 'Invalid JSON response.' };
            }
            if (options.success) options.success(response);
        } else if (options.error) {
            options.error(xhr);
        }
    };
    xhr.onerror = function () {
        if (options.error) options.error(xhr);
    };
    xhr.send(options.data instanceof FormData ? options.data : serialize(options.data || {}));
}

function serialize(obj) {
    var pairs = [];
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]));
        }
    }
    return pairs.join('&');
}

function initGlobalSearch() {
    var input = document.getElementById('globalSearch');
    if (!input) return;

    var timer = null;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        var query = input.value.trim();
        if (!query) return;
        timer = setTimeout(function () {
            ajaxRequest({
                url: ajaxUrl(),
                method: 'POST',
                data: { action: 'live_search', query: query },
                success: function (response) {
                    if (response.success && Array.isArray(response.results)) {
                        // TODO: Render suggestion dropdown in UI if needed.
                        console.log('Search suggestions', response.results);
                    }
                }
            });
        }, 250);
    });
}

function initMobileNav() {
    var toggle = document.getElementById('mobileNavToggle');
    var overlay = document.getElementById('mobileNavOverlay');
    var sidebar = document.querySelector('.sidebar');
    if (!toggle || !overlay || !sidebar) return;

    function closeMobileNav() {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mobile-nav-open');
    }

    function openMobileNav() {
        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mobile-nav-open');
    }

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('is-open')) {
            closeMobileNav();
        } else {
            openMobileNav();
        }
    });

    overlay.addEventListener('click', closeMobileNav);
    sidebar.querySelectorAll('.nav-link, .sidebar-logout').forEach(function (link) {
        link.addEventListener('click', closeMobileNav);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            closeMobileNav();
        }
    });
}

// Initialize lucide icons on page load
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
} else {
    setTimeout(function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }, 500);
}

initThemeToggle();
initModals();
initGlobalSearch();
initMobileNav();
