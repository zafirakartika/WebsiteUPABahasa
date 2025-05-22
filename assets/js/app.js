// assets/js/app.js
// Main JavaScript Application for UPA Bahasa UPNVJ

/**
 * Application Configuration
 */
const APP_CONFIG = {
    API_BASE_URL: '/api',
    TOAST_DURATION: 5000,
    LOADING_TIMEOUT: 30000,
    DATE_FORMAT: 'dd/mm/yyyy',
    TIME_FORMAT: 'HH:mm'
};

/**
 * Utility Functions
 */
const Utils = {
    /**
     * Show loading state on element
     */
    showLoading: function(element, text = 'Memuat...') {
        if (element.tagName === 'BUTTON') {
            element.dataset.originalText = element.innerHTML;
            element.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${text}`;
            element.disabled = true;
        } else {
            element.innerHTML = `
                <div class="d-flex align-items-center justify-content-center p-4">
                    <div class="spinner-border me-3"></div>
                    <span>${text}</span>
                </div>
            `;
        }
    },

    /**
     * Hide loading state on element
     */
    hideLoading: function(element) {
        if (element.tagName === 'BUTTON' && element.dataset.originalText) {
            element.innerHTML = element.dataset.originalText;
            element.disabled = false;
            delete element.dataset.originalText;
        }
    },

    /**
     * Show toast notification
     */
    showToast: function(message, type = 'info', duration = APP_CONFIG.TOAST_DURATION) {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast-container .toast');
        existingToasts.forEach(toast => toast.remove());

        // Create toast container if it doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1060';
            document.body.appendChild(container);
        }

        // Create toast element
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            success: 'check-circle-fill',
            error: 'exclamation-triangle-fill',
            warning: 'exclamation-triangle-fill',
            info: 'info-circle-fill'
        };

        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type}" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${iconMap[type]} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        toast.show();

        // Auto remove after duration
        setTimeout(() => {
            if (toastElement) toastElement.remove();
        }, duration + 500);
    },

    /**
     * Format date to Indonesian format
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return date.toLocaleDateString('id-ID', options);
    },

    /**
     * Format currency to Indonesian Rupiah
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR'
        }).format(amount);
    },

    /**
     * Validate email format
     */
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    /**
     * Validate Indonesian phone number
     */
    isValidPhone: function(phone) {
        const phoneRegex = /^(\+62|62|0)8[1-9][0-9]{6,9}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    },

    /**
     * Debounce function
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

/**
 * API Helper Functions
 */
const API = {
    /**
     * Make API request
     */
    request: async function(endpoint, options = {}) {
        const url = `${APP_CONFIG.API_BASE_URL}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            }
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Something went wrong');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * GET request
     */
    get: function(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    /**
     * POST request
     */
    post: function(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    /**
     * PUT request
     */
    put: function(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    /**
     * DELETE request
     */
    delete: function(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

/**
 * Form Handling
 */
const FormHandler = {
    /**
     * Handle form submission with AJAX
     */
    handleSubmit: function(form, options = {}) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Show loading state
            if (submitBtn) Utils.showLoading(submitBtn, 'Memproses...');

            try {
                const response = await API.post(options.endpoint || form.action, data);
                
                if (response.success) {
                    Utils.showToast(response.message || 'Berhasil!', 'success');
                    
                    if (options.onSuccess) {
                        options.onSuccess(response);
                    } else if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                } else {
                    Utils.showToast(response.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                Utils.showToast(error.message || 'Terjadi kesalahan', 'error');
            } finally {
                if (submitBtn) Utils.hideLoading(submitBtn);
            }
        });
    },

    /**
     * Validate form fields
     */
    validate: function(form) {
        const fields = form.querySelectorAll('[required]');
        let isValid = true;

        fields.forEach(field => {
            const value = field.value.trim();
            const fieldName = field.name || field.id;

            // Remove existing error styling
            field.classList.remove('is-invalid');
            const feedback = field.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.remove();

            // Check if field is empty
            if (!value) {
                this.showFieldError(field, `${fieldName} harus diisi`);
                isValid = false;
                return;
            }

            // Email validation
            if (field.type === 'email' && !Utils.isValidEmail(value)) {
                this.showFieldError(field, 'Format email tidak valid');
                isValid = false;
                return;
            }

            // Phone validation
            if (field.name === 'no_telpon' && !Utils.isValidPhone(value)) {
                this.showFieldError(field, 'Format nomor telepon tidak valid');
                isValid = false;
                return;
            }

            // Password confirmation
            if (field.name === 'confirm_password') {
                const password = form.querySelector('[name="password"]').value;
                if (value !== password) {
                    this.showFieldError(field, 'Konfirmasi password tidak cocok');
                    isValid = false;
                    return;
                }
            }
        });

        return isValid;
    },

    /**
     * Show field error
     */
    showFieldError: function(field, message) {
        field.classList.add('is-invalid');
        
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        
        field.parentNode.appendChild(feedback);
    }
};

/**
 * Data Table Handler
 */
const DataTable = {
    /**
     * Initialize DataTable
     */
    init: function(selector, options = {}) {
        const defaultOptions = {
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        };

        const config = { ...defaultOptions, ...options };
        
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().destroy();
        }
        
        return $(selector).DataTable(config);
    }
};

/**
 * Chart Helper
 */
const ChartHelper = {
    /**
     * Create doughnut chart for test scores
     */
    createScoreChart: function(canvasId, scores) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Listening', 'Structure', 'Reading'],
                datasets: [{
                    data: [scores.listening, scores.structure, scores.reading],
                    backgroundColor: ['#667eea', '#17a2b8', '#28a745'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '/250';
                            }
                        }
                    }
                }
            }
        });
    },

    /**
     * Create progress chart
     */
    createProgressChart: function(canvasId, current, total) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const remaining = total - current;
        
        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Tersisa'],
                datasets: [{
                    data: [current, remaining],
                    backgroundColor: ['#28a745', '#e9ecef'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
};

/**
 * Page-specific handlers
 */
const PageHandlers = {
    /**
     * Dashboard page
     */
    dashboard: function() {
        // Auto-refresh data every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    /**
     * Registration page
     */
    registration: function() {
        // Date selection handler
        const dateOptions = document.querySelectorAll('.date-option');
        dateOptions.forEach(option => {
            option.addEventListener('click', function() {
                if (this.classList.contains('unavailable')) return;

                // Remove selected class from all options
                dateOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update radio button
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            });
        });

        // Form validation
        const form = document.querySelector('#registrationForm');
        if (form) {
            FormHandler.handleSubmit(form, {
                endpoint: '/elpt/register.php',
                onSuccess: function(response) {
                    Utils.showToast('Pendaftaran berhasil! Billing Number: ' + response.billing_number, 'success', 10000);
                }
            });
        }
    },

    /**
     * Results page
     */
    results: function() {
        // Initialize score charts
        const charts = document.querySelectorAll('[id$="Chart"]');
        charts.forEach(chart => {
            const scores = JSON.parse(chart.dataset.scores || '{}');
            if (scores.listening !== undefined) {
                ChartHelper.createScoreChart(chart.id, scores);
            }
        });

        // Certificate download handler
        const downloadBtns = document.querySelectorAll('.download-certificate');
        downloadBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const resultId = this.dataset.resultId;
                window.open(`/api/elpt/download-certificate.php?result_id=${resultId}`, '_blank');
            });
        });
    },

    /**
     * Admin pages
     */
    admin: function() {
        // Initialize DataTables
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            DataTable.init(table, {
                order: [[0, 'desc']] // Sort by first column descending
            });
        });

        // Confirmation dialogs
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('confirm-action')) {
                e.preventDefault();
                const message = e.target.dataset.message || 'Apakah Anda yakin?';
                if (confirm(message)) {
                    window.location.href = e.target.href;
                }
            }
        });

        // Auto-refresh admin dashboard
        if (window.location.pathname.includes('/admin/dashboard.php')) {
            setInterval(() => {
                // Refresh statistics without full page reload
                fetch('/api/admin/stats.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update statistics cards
                            Object.keys(data.stats).forEach(key => {
                                const element = document.querySelector(`[data-stat="${key}"]`);
                                if (element) {
                                    element.textContent = data.stats[key];
                                }
                            });
                        }
                    })
                    .catch(error => console.error('Stats refresh error:', error));
            }, 60000); // Refresh every minute
        }
    }
};

/**
 * Search and Filter functionality
 */
const SearchFilter = {
    /**
     * Initialize search with debouncing
     */
    init: function(inputSelector, targetSelector, delay = 300) {
        const searchInput = document.querySelector(inputSelector);
        const targetElements = document.querySelectorAll(targetSelector);

        if (!searchInput || !targetElements.length) return;

        const debouncedSearch = Utils.debounce((query) => {
            this.performSearch(query, targetElements);
        }, delay);

        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value.toLowerCase());
        });
    },

    /**
     * Perform search on elements
     */
    performSearch: function(query, elements) {
        elements.forEach(element => {
            const text = element.textContent.toLowerCase();
            const shouldShow = !query || text.includes(query);
            
            element.style.display = shouldShow ? '' : 'none';
            
            // Add highlight to matching text
            if (query && shouldShow) {
                this.highlightText(element, query);
            } else {
                this.removeHighlight(element);
            }
        });
    },

    /**
     * Highlight matching text
     */
    highlightText: function(element, query) {
        // Simple highlighting - can be enhanced with more sophisticated logic
        const text = element.innerHTML;
        const regex = new RegExp(`(${query})`, 'gi');
        element.innerHTML = text.replace(regex, '<mark>$1</mark>');
    },

    /**
     * Remove text highlighting
     */
    removeHighlight: function(element) {
        const marks = element.querySelectorAll('mark');
        marks.forEach(mark => {
            mark.outerHTML = mark.innerHTML;
        });
    }
};

/**
 * File Upload Handler
 */
const FileUpload = {
    /**
     * Handle file upload with progress
     */
    upload: function(fileInput, options = {}) {
        const files = fileInput.files;
        if (!files.length) return;

        const formData = new FormData();
        Array.from(files).forEach(file => {
            formData.append('files[]', file);
        });

        const xhr = new XMLHttpRequest();
        
        // Progress handler
        if (options.onProgress) {
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    options.onProgress(percentComplete);
                }
            });
        }

        // Success handler
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (options.onSuccess) options.onSuccess(response);
            } else {
                if (options.onError) options.onError('Upload failed');
            }
        });

        // Error handler
        xhr.addEventListener('error', function() {
            if (options.onError) options.onError('Upload failed');
        });

        xhr.open('POST', options.endpoint || '/api/upload.php');
        xhr.send(formData);
    },

    /**
     * Validate file before upload
     */
    validate: function(file, options = {}) {
        const maxSize = options.maxSize || 5 * 1024 * 1024; // 5MB default
        const allowedTypes = options.allowedTypes || ['image/jpeg', 'image/png', 'application/pdf'];
        
        if (file.size > maxSize) {
            Utils.showToast('File terlalu besar. Maksimal ' + (maxSize / 1024 / 1024) + 'MB', 'error');
            return false;
        }
        
        if (!allowedTypes.includes(file.type)) {
            Utils.showToast('Tipe file tidak diizinkan', 'error');
            return false;
        }
        
        return true;
    }
};

/**
 * Local Storage Helper
 */
const Storage = {
    /**
     * Set item in localStorage
     */
    set: function(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('LocalStorage set error:', error);
        }
    },

    /**
     * Get item from localStorage
     */
    get: function(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('LocalStorage get error:', error);
            return defaultValue;
        }
    },

    /**
     * Remove item from localStorage
     */
    remove: function(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.error('LocalStorage remove error:', error);
        }
    },

    /**
     * Clear all localStorage
     */
    clear: function() {
        try {
            localStorage.clear();
        } catch (error) {
            console.error('LocalStorage clear error:', error);
        }
    }
};

/**
 * Application Initialization
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Initialize page-specific handlers
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('dashboard')) {
        PageHandlers.dashboard();
    }
    
    if (currentPage.includes('registration')) {
        PageHandlers.registration();
    }
    
    if (currentPage.includes('results')) {
        PageHandlers.results();
    }
    
    if (currentPage.includes('/admin/')) {
        PageHandlers.admin();
    }

    // Initialize search functionality
    SearchFilter.init('.search-input', '.searchable-item');

    // Back to top button functionality
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.remove('d-none');
            } else {
                backToTopBtn.classList.add('d-none');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Form enhancement
    const forms = document.querySelectorAll('form[data-ajax="true"]');
    forms.forEach(form => {
        FormHandler.handleSubmit(form);
    });

    // Real-time form validation
    const inputsWithValidation = document.querySelectorAll('input[required], select[required], textarea[required]');
    inputsWithValidation.forEach(input => {
        input.addEventListener('blur', function() {
            const form = this.closest('form');
            if (form) {
                FormHandler.validate(form);
            }
        });
    });

    // Handle file uploads
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const files = Array.from(this.files);
            files.forEach(file => {
                if (!FileUpload.validate(file)) {
                    this.value = ''; // Clear invalid file
                }
            });
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('.search-input, input[type="search"]');
            if (searchInput) {
                searchInput.focus();
            }
        }

        // Escape to close modals
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            });
        }
    });

    // Save form data to localStorage for recovery
    const formsToSave = document.querySelectorAll('form[data-autosave="true"]');
    formsToSave.forEach(form => {
        const formId = form.id || 'form-' + Math.random().toString(36).substr(2, 9);
        
        // Load saved data
        const savedData = Storage.get('form-' + formId);
        if (savedData) {
            Object.keys(savedData).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && field.type !== 'password') {
                    field.value = savedData[key];
                }
            });
        }

        // Save data on input
        const saveFormData = Utils.debounce(() => {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            Storage.set('form-' + formId, data);
        }, 1000);

        form.addEventListener('input', saveFormData);
        
        // Clear saved data on successful submission
        form.addEventListener('submit', function() {
            Storage.remove('form-' + formId);
        });
    });

    // Initialize PWA features if supported
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered:', registration);
            })
            .catch(error => {
                console.log('SW registration failed:', error);
            });
    }

    // Performance monitoring
    if (window.performance && window.performance.timing) {
        window.addEventListener('load', function() {
            const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
            console.log('Page load time:', loadTime + 'ms');
            
            // Send to analytics if configured
            if (window.gtag) {
                gtag('event', 'page_load_time', {
                    value: loadTime,
                    custom_parameter: window.location.pathname
                });
            }
        });
    }
});

/**
 * Global error handler
 */
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    
    // Show user-friendly error message
    Utils.showToast('Terjadi kesalahan. Silakan refresh halaman.', 'error');
    
    // Send to error tracking service if configured
    if (window.Sentry) {
        Sentry.captureException(e.error);
    }
});

/**
 * Unhandled promise rejection handler
 */
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    
    // Show user-friendly error message
    Utils.showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
    
    // Send to error tracking service if configured
    if (window.Sentry) {
        Sentry.captureException(e.reason);
    }
});

// Export for use in other scripts
window.APP = {
    Utils,
    API,
    FormHandler,
    DataTable,
    ChartHelper,
    SearchFilter,
    FileUpload,
    Storage,
    PageHandlers
};