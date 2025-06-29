
// transaksi.js - JavaScript functions for transaction management

// Format Rupiah function with better visual feedback
function formatRupiah(element) {
    let value = element.value.replace(/[^0-9]/g, '');
    if (value) {
        let formatted = new Intl.NumberFormat('id-ID').format(value);
        element.value = formatted;
        document.getElementById('amount').value = value;
        
        // Add visual feedback
        element.classList.add('ring-2', 'ring-green-300');
        setTimeout(() => {
            element.classList.remove('ring-2', 'ring-green-300');
        }, 300);
    } else {
        element.value = '';
        document.getElementById('amount').value = '';
    }
}

// JavaScript untuk mengupdate field 'amount' secara otomatis saat memilih produk dan mengisi quantity
function updateAmountField() {
    const productIdSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const amountDisplayInput = document.getElementById('amount_display');
    const amountInput = document.getElementById('amount');
    const quantityInfo = document.getElementById('quantity-info'); 

    if (productIdSelect && quantityInput && amountDisplayInput && amountInput && quantityInfo) {
        const selectedOption = productIdSelect.options[productIdSelect.selectedIndex];
        
        // If "Pilih Produk" is selected or no product is selected
        if (selectedOption.value === "") {
            amountDisplayInput.value = '';
            amountInput.value = '';
            quantityInput.value = ''; // Clear quantity
            quantityInput.disabled = true; // Disable quantity input
            quantityInfo.classList.remove('hidden'); // Show message
            return; // Exit early
        }
        
        // If a product is selected, enable quantity input and hide message
        quantityInput.disabled = false;
        quantityInfo.classList.add('hidden'); // Hide message

        const price = parseFloat(selectedOption.dataset.price);
        const currentStock = parseInt(selectedOption.dataset.stock);
        let quantity = parseInt(quantityInput.value);

        // Validate quantity input (ensure it's a positive number)
        if (isNaN(quantity) || quantity <= 0) {
            amountDisplayInput.value = '';
            amountInput.value = '';
            return; // Don't proceed with amount calculation if quantity is invalid
        }

        // Validate quantity against current stock
        if (quantity > currentStock) {
            alert('Jumlah unit yang dimasukkan melebihi stok yang tersedia (' + currentStock + ' unit).');
            quantityInput.value = currentStock; // Set quantity to max available stock
            quantity = currentStock; // Update quantity variable for calculation
        }

        // Calculate and set amount
        if (!isNaN(price) && !isNaN(quantity) && quantity > 0) {
            const totalAmount = price * quantity;
            amountInput.value = totalAmount;
            amountDisplayInput.value = new Intl.NumberFormat('id-ID').format(totalAmount);
        } else {
            amountDisplayInput.value = '';
            amountInput.value = '';
        }
    }
}

// Function to populate form for editing a transaction
function editTransaction(transaction, allProducts) {
    document.getElementById('transaction_id_to_edit').value = transaction.id;
    document.getElementById('date').value = transaction.date;
    
    // Set amount values
    const amountValue = parseFloat(transaction.amount);
    document.getElementById('amount').value = amountValue;
    document.getElementById('amount_display').value = new Intl.NumberFormat('id-ID').format(amountValue);
    
    document.getElementById('description').value = transaction.description;

    const submitButton = document.getElementById('submit_button');
    const cancelButton = document.getElementById('cancel_edit_button');
    
    submitButton.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>Update Transaksi';
    submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
    submitButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700'); // Change color for edit
    cancelButton.classList.remove('hidden');

    // Logic for 'pemasukan' (sale) type specific fields
    if (transaction.type === 'pemasukan') {
        const productIdSelect = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');
        const quantityInfo = document.getElementById('quantity-info');

        productIdSelect.value = transaction.product_id;
        quantityInput.value = transaction.quantity;
        quantityInput.disabled = false; // Enable quantity input
        quantityInfo.classList.add('hidden'); // Hide message

        // Adjust stock for calculation if product quantity is being edited
        const selectedProductOption = productIdSelect.options[productIdSelect.selectedIndex];
        if (selectedProductOption) {
            // Temporarily add back the original quantity to stock for correct re-calculation
            let originalStock = parseInt(selectedProductOption.dataset.stock);
            let currentTransQuantity = parseInt(transaction.quantity);
            selectedProductOption.dataset.stock = originalStock + currentTransQuantity;
        }

        updateAmountField(); // Recalculate amount based on pre-filled data
    }
    
    // Scroll to top to make the form visible
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Function to reset the form to 'add new' state
function resetForm() {
    document.getElementById('transaction_id_to_edit').value = '';
    document.getElementById('date').value = new Date().toISOString().split('T')[0]; // Set to today's date
    document.getElementById('amount').value = '';
    document.getElementById('amount_display').value = '';
    document.getElementById('description').value = '';

    const submitButton = document.getElementById('submit_button');
    const cancelButton = document.getElementById('cancel_edit_button');

    submitButton.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Simpan Transaksi';
    submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
    submitButton.classList.add('bg-blue-600', 'hover:bg-blue-700'); // Revert color
    cancelButton.classList.add('hidden');

    // Reset fields specific to 'pemasukan'
    const productIdSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const quantityInfo = document.getElementById('quantity-info');

    if (productIdSelect) {
        productIdSelect.value = '';
    }
    if (quantityInput) {
        quantityInput.value = '';
        quantityInput.disabled = true;
    }
    if (quantityInfo) quantityInfo.classList.remove('hidden');
    
    // Reset amount display
    const amountDisplayInput = document.getElementById('amount_display');
    if (amountDisplayInput) {
        // Get transaction type from page context
        const typeInput = document.querySelector('input[name="type"]');
        const currentType = typeInput ? typeInput.value : 'pemasukan';
        
        if (currentType === 'pengeluaran') {
            amountDisplayInput.readOnly = false;
            amountDisplayInput.classList.remove('bg-gray-50');
        } else {
            amountDisplayInput.readOnly = true;
            amountDisplayInput.classList.add('bg-gray-50');
        }
    }

    // Re-run initial check for quantity field status
    if (productIdSelect && quantityInput && quantityInfo) {
        if (productIdSelect.value === "") {
            quantityInput.disabled = true;
            quantityInfo.classList.remove('hidden');
        } else {
            quantityInput.disabled = false;
            quantityInfo.classList.add('hidden');
        }
    }
}

// Function to reset filter
function resetFilter() {
    const typeInput = document.querySelector('input[name="type"]');
    const currentType = typeInput ? typeInput.value : 'pemasukan';
    window.location.href = '?type=' + currentType;
}

// Initialize transaction functionality
function initializeTransactionPage() {
    const quantityInput = document.getElementById('quantity');
    const productIdSelect = document.getElementById('product_id');
    const amountDisplayInput = document.getElementById('amount_display');

    // Initial check for quantity field status on page load
    if (productIdSelect && productIdSelect.value === "") {
        if (quantityInput) {
            quantityInput.disabled = true;
        }
        const quantityInfo = document.getElementById('quantity-info');
        if (quantityInfo) {
            quantityInfo.classList.remove('hidden');
        }
    } else if (productIdSelect) {
        if (quantityInput) {
            quantityInput.disabled = false;
        }
        const quantityInfo = document.getElementById('quantity-info');
        if (quantityInfo) {
            quantityInfo.classList.add('hidden');
        }
        updateAmountField(); 
    }

    // Add event listeners
    if (quantityInput) {
        quantityInput.addEventListener('change', updateAmountField);
        quantityInput.addEventListener('keyup', updateAmountField);
    }
    if (productIdSelect) {
        productIdSelect.addEventListener('change', updateAmountField);
    }

    // Format rupiah for expense transactions
    const typeInput = document.querySelector('input[name="type"]');
    const currentType = typeInput ? typeInput.value : 'pemasukan';
    
    if (currentType === 'pengeluaran' && amountDisplayInput) {
        amountDisplayInput.addEventListener('input', function() {
            formatRupiah(this);
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeTransactionPage);
