
// laporan_keuangan.js - Handle financial report functionality

function toggleCustomDate() {
    const dateFilter = document.querySelector('select[name="date_filter"]').value;
    const customRange = document.getElementById('custom-date-range');
    if (dateFilter === 'custom') {
        customRange.style.display = 'grid';
    } else {
        customRange.style.display = 'none';
    }
}

function initializeJurnalUmum() {
    // Real-time search untuk jurnal umum
    let searchTimeout;
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                document.querySelector('form').submit();
            }, 500);
        });
    }
}

function exportData(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    params.delete('page'); // Reset pagination for export
    
    // Create a form to submit the export request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_laporan.php';
    form.target = '_blank';
    
    // Add all current parameters as hidden inputs
    for (const [key, value] of params.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date filter toggle
    const dateFilterSelect = document.querySelector('select[name="date_filter"]');
    if (dateFilterSelect) {
        dateFilterSelect.addEventListener('change', toggleCustomDate);
        // Initialize on page load
        toggleCustomDate();
    }
});
