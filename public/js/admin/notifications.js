document.addEventListener('DOMContentLoaded', function () {
    if (!window.Echo) {
        console.error('Echo not initialized');
        return;
    }

    const channel = window.Echo.private('admin-alerts');

    // Your actual listener
    channel.listen('.new.notification', (payload) => {
        injectNotification(payload);
        incrementBadge();
        triggerSwalForType(payload);
    });
});

function buildBadgeColor(type) {
    if (type === 'request' || type === 'order_request') return 'primary';
    if (type === 'warning' || type === 'low_stock')     return 'warning';
    return 'secondary';
}

function injectNotification(n) {
    const list = document.querySelector('ul.notification-dropdown');
    if (!list) return;

    // Remove empty state if present
    list.querySelectorAll('li.text-center').forEach(el => el.remove());

    const divider = list.querySelector('li:first-child')
        ? `<hr class="dropdown-divider my-1" id="divider-${n.id}">`
        : '';

    const userLine = n.user_name
        ? `<div class="text-small">${n.user_name}</div>`
        : '';

    const html = `
        ${divider}
        <li id="notification-${n.id}">
            <div class="dropdown-item notification-item d-flex justify-content-between align-items-start fw-semibold"
                 data-id="${n.id}"
                 data-url="${n.url}">

                <div style="flex:1; cursor:pointer;"
                     onclick="handleNotificationClick(this)"
                     data-id="${n.id}"
                     data-url="${n.url}">

                    <div class="d-flex justify-content-between flex-wrap">
                        <span class="badge bg-${buildBadgeColor(n.type)} text-dark mb-1">
                            ${n.type.charAt(0).toUpperCase() + n.type.slice(1)}
                        </span>
                        <small class="text-muted mb-1">${n.created_at}</small>
                    </div>

                    <div style="white-space:normal;">${n.message}</div>
                    ${userLine}
                </div>

                <button class="btn btn-sm btn-link text-danger ms-2"
                        onclick="deleteNotification(event, ${n.id})"
                        title="Löschen">🗑️</button>
            </div>
        </li>
    `;

    list.insertAdjacentHTML('afterbegin', html);
}

function incrementBadge() {
    const badge = document.querySelector('.notification-badge');
    if (!badge) return;
    const current = parseInt(badge.textContent.trim()) || 0;
    badge.textContent = current + 1;
    badge.style.display = 'inline-block';
}

function triggerSwalForType(payload) {
    if (typeof Swal === 'undefined') {
        console.warn('SweetAlert2 not loaded');
        return;
    }

    const configs = {
        low_stock: {
            title: 'Warnung: Niedriger Bestand',
            icon:  'warning',
        },
        order_request: {
            title: 'Neue Bestellanfrage',
            icon:  'info',
        },
        warning: {
            title: 'Warnung',
            icon:  'warning',
        },
    };

    const config = configs[payload.type] ?? {
        title: 'Neue Benachrichtigung',
        icon:  'info',
    };

    Swal.fire({
        title:             config.title,
        text:              payload.message,
        icon:              config.icon,
        showCancelButton:  true,
        confirmButtonText: 'Ansehen',
        cancelButtonText:  'Schließen',
        timer:             8000,       // auto-close after 8s
        timerProgressBar:  true,
    }).then((result) => {
        if (result.isConfirmed) {
            if (window.triggerMarkAsRead) {
                window.triggerMarkAsRead(payload.id, payload.url);
            } else {
                window.location.href = payload.url;
            }
        }
    });
}