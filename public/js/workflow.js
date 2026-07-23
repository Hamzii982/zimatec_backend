/* Project workflow JS — fetch wrappers + Web Animations API */

(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrf) {
        return;
    }

    async function sendJson(url, method, body) {
        const res = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: body ? JSON.stringify(body) : undefined,
        });

        if (!res.ok) {
            const text = await res.text();
            throw new Error(text || res.statusText);
        }

        return res.json();
    }

    function animateOut(card) {
        return card.animate(
            [
                { opacity: 1, transform: 'translateX(0) scale(1)' },
                { opacity: 0, transform: 'translateX(-20px) scale(.95)' },
            ],
            { duration: 280, easing: 'ease', fill: 'forwards' }
        ).finished;
    }

    function animateIn(card) {
        return card.animate(
            [
                { opacity: 0, transform: 'translateX(20px) scale(.95)' },
                { opacity: 1, transform: 'translateX(0) scale(1)' },
            ],
            { duration: 360, easing: 'ease', fill: 'forwards' }
        ).finished;
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!form.matches('form[data-workflow-form]')) {
            return;
        }

        event.preventDefault();

        try {
            const data = await sendJson(form.action, form.method, Object.fromEntries(new FormData(form)));
            Swal.fire({
                icon: 'success',
                title: data.message || 'OK',
                timer: 1500,
                showConfirmButton: false,
            });

            if (data.reload) {
                setTimeout(() => window.location.reload(), 600);
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Fehler', text: err.message });
        }
    });

    // Card move on advance: animate current card out, reload page on success.
    document.addEventListener('click', async (event) => {
        const advanceBtn = event.target.closest('[data-workflow-advance]');
        if (!advanceBtn) {
            return;
        }

        event.preventDefault();

        const form = advanceBtn.closest('form');
        if (!form) {
            return;
        }

        const result = await Swal.fire({
            title: 'Weitergeben?',
            text: 'Projekt an die nächste Stufe übergeben?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ja, weitergeben',
            cancelButtonText: 'Abbrechen',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            const data = await sendJson(form.action, form.method);

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Weitergegeben', timer: 1200, showConfirmButton: false });
            }

            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            }, 700);
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Fehler', text: err.message });
        }
    });
})();
