/* Project workflow JS — inline assignees + Web Animations API */

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

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function animateOut(card) {
        if (prefersReducedMotion()) {
            return Promise.resolve();
        }
        return card.animate(
            [
                { opacity: 1, transform: 'translateX(0) scale(1)' },
                { opacity: 0, transform: 'translateX(-20px) scale(.95)' },
            ],
            { duration: 280, easing: 'ease', fill: 'forwards' }
        ).finished;
    }

    function animateIn(card) {
        if (prefersReducedMotion()) {
            return Promise.resolve();
        }
        return card.animate(
            [
                { opacity: 0, transform: 'translateX(20px) scale(.95)' },
                { opacity: 1, transform: 'translateX(0) scale(1)' },
            ],
            { duration: 360, easing: 'ease', fill: 'forwards' }
        ).finished;
    }

    function reloadOnSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: message || 'OK', timer: 1100, showConfirmButton: false });
        }
        setTimeout(() => window.location.reload(), 700);
    }

    // Card move on advance
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
            await sendJson(form.action, form.method);
            reloadOnSuccess('Weitergegeben');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Fehler', text: err.message });
        }
    });

    // Per-step assignee: add via dropdown option click
    document.addEventListener('click', async (event) => {
        const option = event.target.closest('[data-workflow-assign-option]');
        if (!option) {
            return;
        }

        event.preventDefault();

        const stepId = option.dataset.step;
        const userId = option.dataset.user;
        const projectId = option.closest('[data-workflow-step]')?.dataset.projectId
            || document.querySelector('[data-workflow-project]')?.dataset.workflowProject
            || document.querySelector('[data-workflow-board]')?.dataset.workflowProject;

        const url = `/workflow/projects/${document.body.dataset.workflowProjectId || projectId}/steps/${stepId}/assignees`;

        // Prefer the global route resolver if exposed
        const storeUrl = window.workflowRoutes?.assigneesStore
            ? window.workflowRoutes.assigneesStore
                .replace('__PROJECT__', document.body.dataset.workflowProjectId)
                .replace('__STEP__', stepId)
            : url;

        try {
            await sendJson(storeUrl, 'POST', { user_id: parseInt(userId, 10) });
            reloadOnSuccess('Bearbeiter hinzugefügt');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Fehler', text: err.message });
        }
    });

    // Per-step assignee: remove via X button on a pill
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-workflow-unassign]');
        if (!btn) {
            return;
        }

        event.preventDefault();

        const stepId = btn.dataset.step;
        const userId = btn.dataset.user;

        const destroyUrl = window.workflowRoutes?.assigneesDestroy
            ? window.workflowRoutes.assigneesDestroy
                .replace('__PROJECT__', document.body.dataset.workflowProjectId)
                .replace('__STEP__', stepId)
                .replace('__USER__', userId)
            : `/workflow/projects/${document.body.dataset.workflowProjectId}/steps/${stepId}/assignees/${userId}`;

        try {
            await sendJson(destroyUrl, 'DELETE');
            reloadOnSuccess('Bearbeiter entfernt');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Fehler', text: err.message });
        }
    });
})();
