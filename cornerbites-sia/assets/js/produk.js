
// Produk JavaScript Functions

// Format Rupiah function
function formatRupiah(element, hiddenInputId) {
    let value = element.value.replace(/[^0-9]/g, '');
    
    if (value === '') {
        element.value = '';
        document.getElementById(hiddenInputId).value = '';
        return;
    }
    
    let formatted = new Intl.NumberFormat('id-ID').format(value);
    element.value = formatted;
    document.getElementById(hiddenInputId).value = value;
}

// JavaScript untuk mengisi form saat tombol edit diklik
function editProduct(product) {
    document.getElementById('product_id_to_edit').value = product.id;
    document.getElementById('product_name').value = product.name;
    
    // Handle unit dropdown
    const unitSelect = document.getElementById('unit');
    const customInput = document.getElementById('unit_custom');
    const unitOptions = ['pcs', 'porsi', 'bungkus', 'cup', 'botol', 'gelas', 'slice', 'pack', 'box', 'kg', 'gram', 'liter', 'ml'];
    
    if (unitOptions.includes(product.unit)) {
        unitSelect.value = product.unit;
        customInput.classList.add('hidden');
        customInput.required = false;
    } else {
        unitSelect.value = 'custom';
        customInput.value = product.unit;
        customInput.classList.remove('hidden');
        customInput.required = true;
    }
    
    document.getElementById('stock').value = product.stock;
    
    // Format dan set harga beli
    document.getElementById('cost_price_display').value = new Intl.NumberFormat('id-ID').format(product.cost_price);
    document.getElementById('cost_price').value = product.cost_price;
    
    // Format dan set harga jual
    document.getElementById('sale_price_display').value = new Intl.NumberFormat('id-ID').format(product.sale_price);
    document.getElementById('sale_price').value = product.sale_price;

    document.getElementById('submit_button').innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
        </svg>
        Update Produk
    `;
    document.getElementById('submit_button').classList.remove('bg-green-600', 'hover:bg-green-700');
    document.getElementById('submit_button').classList.add('bg-blue-600', 'hover:bg-blue-700');
    document.getElementById('cancel_edit_button').classList.remove('hidden');
    
    // Scroll to top to make the form visible
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// JavaScript untuk mereset form
function resetForm() {
    document.getElementById('product_id_to_edit').value = '';
    document.getElementById('product_name').value = '';
    
    // Reset unit dropdown
    document.getElementById('unit').value = '';
    document.getElementById('unit_custom').value = '';
    document.getElementById('unit_custom').classList.add('hidden');
    document.getElementById('unit_custom').required = false;
    
    document.getElementById('stock').value = '';
    
    // Reset display dan hidden inputs untuk harga
    document.getElementById('cost_price_display').value = '';
    document.getElementById('cost_price').value = '';
    document.getElementById('sale_price_display').value = '';
    document.getElementById('sale_price').value = '';

    document.getElementById('submit_button').innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Tambah Produk
    `;
    document.getElementById('submit_button').classList.remove('bg-blue-600', 'hover:bg-blue-700');
    document.getElementById('submit_button').classList.add('bg-green-600', 'hover:bg-green-700');
    document.getElementById('cancel_edit_button').classList.add('hidden');
}

// Toggle custom unit input
function toggleCustomUnit() {
    const unitSelect = document.getElementById('unit');
    const customInput = document.getElementById('unit_custom');
    
    if (unitSelect.value === 'custom') {
        customInput.classList.remove('hidden');
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.classList.add('hidden');
        customInput.required = false;
        customInput.value = '';
    }
}

// Validate form before submit
function validateForm() {
    const unitSelect = document.getElementById('unit');
    const customInput = document.getElementById('unit_custom');
    
    if (unitSelect.value === 'custom' && customInput.value.trim() === '') {
        alert('Silakan masukkan satuan custom');
        customInput.focus();
        return false;
    }
    
    return true;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add form validation
    const form = document.querySelector('form[action="/cornerbites-sia/process/simpan_produk.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }
    
    console.log('Produk page loaded');
});
