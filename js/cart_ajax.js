document.addEventListener('DOMContentLoaded', function() {
    // Toast Notification Function
    function showToast(message, type = 'success') {
        const toastContainer = document.createElement('div');
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '20px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '9999';
        
        const toast = document.createElement('div');
        toast.className = `toast show ${type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'}`;
        toast.style.padding = '10px 20px';
        toast.style.borderRadius = '4px';
        toast.style.minWidth = '250px';
        
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="me-2">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                </div>
                <div>
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);
        
        setTimeout(() => {
            toastContainer.remove();
        }, 3000);
    }

    // Add to Cart Handler
    function addToCart(bookId, purchaseType = 'buy', rentalWeeks = 1) {
        console.log('Adding to cart:', bookId, purchaseType, rentalWeeks);
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('book_id', bookId);
        formData.append('purchase_type', purchaseType);
        formData.append('rental_weeks', rentalWeeks);

        fetch('ajax_handlers/cart_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response received');
            return response.json();
        })
        .then(data => {
            console.log('Parsed data:', data);
            if (data.success) {
                showToast(data.message);
                updateCartCount(data.cartCount);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An unexpected error occurred', 'error');
        });
    }

    // Update Cart Count in Header
    function updateCartCount(count) {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
        }
    }

    // Attach event listeners to Add to Cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            // Check if user is a guest (using global function injected in footer)
            if (typeof window.isGuest !== 'undefined' && window.isGuest) {
                if (typeof requireLogin === 'function') {
                    return requireLogin(event, true);
                }
            }
            
            const bookId = this.dataset.bookId;
            const purchaseType = this.dataset.purchaseType || 'buy';
            
            // For rent, get rental weeks from selector
            const rentalWeeks = purchaseType === 'rent' 
                ? document.getElementById('rental_weeks').value 
                : 1;
            
            addToCart(bookId, purchaseType, rentalWeeks);
        });
    });
});