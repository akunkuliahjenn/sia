
// Dashboard JavaScript Functions
let searchTimeout;

// Real-time search function
function applySearchRealtime() {
    const search = document.getElementById('search_input').value;
    const typeFilter = document.getElementById('type_filter').value;
    const dateFilter = document.getElementById('date_filter').value;
    const limit = document.getElementById('limit_select').value;
    
    // Update URL with current filters
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('search', search);
    currentUrl.searchParams.set('type_filter', typeFilter);
    currentUrl.searchParams.set('date_filter', dateFilter);
    currentUrl.searchParams.set('limit', limit);
    currentUrl.searchParams.set('page', '1'); // Reset to first page
    
    // Redirect to updated URL
    window.location.href = currentUrl.toString();
}

// Initialize dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Real-time search for transactions
    const searchInput = document.getElementById('search_input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applySearchRealtime();
            }, 500); // 500ms delay
        });

        // Enter key support
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                clearTimeout(searchTimeout);
                applySearchRealtime();
            }
        });
    }

    // Real-time filter changes
    const typeFilter = document.getElementById('type_filter');
    const dateFilter = document.getElementById('date_filter');
    const limitSelect = document.getElementById('limit_select');

    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applySearchRealtime();
            }, 100);
        });
    }

    if (dateFilter) {
        dateFilter.addEventListener('change', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applySearchRealtime();
            }, 100);
        });
    }

    if (limitSelect) {
        limitSelect.addEventListener('change', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applySearchRealtime();
            }, 100);
        });
    }
});

// Chart initialization functions
function initializeCharts(monthsLabel, monthlySales, monthlyExpenses, popularProductNames, popularProductQuantities) {
    // Inisialisasi Chart.js untuk Tren Penjualan & Pengeluaran Bulanan (Line Chart)
    const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(ctxMonthly, {
        type: 'line',
        data: {
            labels: monthsLabel,
            datasets: [
                {
                    label: 'Penjualan',
                    data: monthlySales,
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                },
                {
                    label: 'Pengeluaran',
                    data: monthlyExpenses,
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            family: 'Inter',
                            size: 12,
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: {
                            family: 'Inter',
                            size: 11,
                        },
                        color: '#6B7280',
                    }
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11,
                        },
                        color: '#6B7280',
                    }
                }
            }
        }
    });

    // Inisialisasi Chart.js untuk Produk Terlaris (Bar Chart)
    const ctxPopular = document.getElementById('popularProductsChart').getContext('2d');
    const popularProductsChart = new Chart(ctxPopular, {
        type: 'bar',
        data: {
            labels: popularProductNames,
            datasets: [{
                label: 'Jumlah Unit Terjual',
                data: popularProductQuantities,
                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                borderColor: 'rgba(147, 51, 234, 1)',
                borderWidth: 1,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.x.toLocaleString('id-ID') + ' unit terjual';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('id-ID');
                        },
                        font: {
                            family: 'Inter',
                            size: 11,
                        },
                        color: '#6B7280',
                    }
                },
                y: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11,
                        },
                        color: '#6B7280',
                    }
                }
            }
        }
    });
}
