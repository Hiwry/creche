// SchoolHub - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Global search functionality
    const globalSearch = document.getElementById('global-search');
    if (globalSearch) {
        let debounceTimer;
        globalSearch.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (e.target.value.length >= 2) {
                    // Could implement AJAX search here
                    console.log('Searching for:', e.target.value);
                }
            }, 300);
        });
    }
    
    // Format currency inputs
    document.querySelectorAll('[data-currency]').forEach(input => {
        input.addEventListener('blur', function(e) {
            const value = parseFloat(e.target.value.replace(/[^\d.,]/g, '').replace(',', '.'));
            if (!isNaN(value)) {
                e.target.value = value.toLocaleString('pt-BR', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                });
            }
        });
    });
    
    // Format phone inputs
    document.querySelectorAll('[data-phone]').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length > 2) {
                    value = '(' + value.substring(0,2) + ') ' + value.substring(2);
                }
                if (value.length > 10) {
                    value = value.substring(0,10) + '-' + value.substring(10);
                }
            }
            e.target.value = value;
        });
    });
    
    // Format CPF inputs
    document.querySelectorAll('[data-cpf]').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length > 3) {
                    value = value.substring(0,3) + '.' + value.substring(3);
                }
                if (value.length > 7) {
                    value = value.substring(0,7) + '.' + value.substring(7);
                }
                if (value.length > 11) {
                    value = value.substring(0,11) + '-' + value.substring(11,13);
                }
            }
            e.target.value = value;
        });
    });
    
    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            if (!confirm(e.target.dataset.confirm || 'Tem certeza que deseja continuar?')) {
                e.preventDefault();
            }
        });
    });
    
    // Toggle sidebar on mobile
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
});

// Helper function to format money
function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Helper function to format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('pt-BR');
}
