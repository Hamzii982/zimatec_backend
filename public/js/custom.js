const alertDiv = document.getElementById('logAlert');
const btn = document.getElementById('runLogBtn');
if (btn != null) {
    const url = btn.dataset.url;

    btn.addEventListener('click', function() {
        alertDiv.innerHTML = `
            <div class="alert alert-info" role="alert">
                Running log parser...
            </div>
        `;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log(data);
                const alertClass = data.status === 'success' ? 'alert-success' : 'alert-danger';
                alertDiv.innerHTML = `
                    <div class="alert ${alertClass}" role="alert">
                        ${data.message}
                    </div>
                `;
            })
            .catch(err => {
                alertDiv.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        Error: ${err.message}
                    </div>
                `;
            });
    });
}

// ===== Function to show Bootstrap alert dynamically =====
function showAlert(message, type = 'success') {
    const alertBox = document.getElementById('logAlert');
    alertBox.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        const alertEl = bootstrap.Alert.getOrCreateInstance(document.querySelector('.alert'));
        if (alertEl) alertEl.close();
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function () {
    const items = document.querySelectorAll('.notification-item');

    // 1. Create a reusable function for marking as read
    async function markAsReadAndRedirect(id, url, element = null) {
        try {
            const res = await fetch(`/admin/notifications/read/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (data.success) {
                // If we have the DOM element (from the dropdown), update the UI
                if (element) {
                    element.classList.remove('fw-semibold');
                }

                // Redirect
                if (url && url !== '#') {
                    window.location.href = url;
                }
            }
        } catch (err) {
            console.error("Fehler:", err);
            // Fallback: Redirect anyway if the server fails but we have a URL
            if (url) window.location.href = url;
        }
    }

    // 2. Attach to dropdown items
    if (items != null) {
        items.forEach(item => {
            item.addEventListener('click', function(e) {
                // Only trigger if we didn't click the delete trash can
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;
                
                e.preventDefault();
                const id = this.dataset.id;
                const url = this.dataset.url;
                markAsReadAndRedirect(id, url, this);
            });
        });
    }

    // 3. Make the function globally available for the Swal popup scripts
    window.triggerMarkAsRead = markAsReadAndRedirect;
});