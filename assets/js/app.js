// assets/js/app.js  –  ClearVoice UI interactions

// ── Delete confirmation modal ─────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Attach click handler to all delete trigger buttons
    document.querySelectorAll('[data-delete-url]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const url   = btn.dataset.deleteUrl;
            const label = btn.dataset.deleteLabel || 'this record';
            openDeleteModal(url, label);
        });
    });

    // Close modal on overlay background click
    const overlay = document.getElementById('deleteModal');
    if (overlay) {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) closeDeleteModal();
        });
    }

    // Keyboard escape closes modal
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeDeleteModal();
    });

    // Auto-dismiss alerts after 4 s
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });

    // Animate table rows on load
    document.querySelectorAll('tbody tr').forEach((tr, i) => {
        tr.style.animationDelay = `${i * 40}ms`;
        tr.classList.add('fade-in');
    });
});

function openDeleteModal(url, label) {
    const overlay = document.getElementById('deleteModal');
    if (!overlay) return;
    document.getElementById('deleteLabel').textContent = label;
    document.getElementById('deleteConfirmBtn').onclick = () => {
        window.location.href = url;
    };
    overlay.classList.add('active');
}

function closeDeleteModal() {
    const overlay = document.getElementById('deleteModal');
    if (overlay) overlay.classList.remove('active');
}
