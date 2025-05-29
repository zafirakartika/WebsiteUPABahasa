<?php
// includes/footer.php
?>
<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <!-- Logo & Description -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-mortarboard me-2" style="font-size: 2rem;"></i>
                    <h5 class="mb-0">UPA Bahasa UPNVJ</h5>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3">Kontak</h6>
                <div class="mb-3">
                    <div class="d-flex align-items-start mb-2">
                        <i class="bi bi-geo-alt me-2 mt-1"></i>
                        <span>
                            Jl. RS. Fatmawati No.1<br>
                            Pondok Labu, Jakarta Selatan 12450
                        </span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        <a href="tel:+622176569292" class="text-light text-decoration-none">
                            (021) 7656-9292
                        </a>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-envelope me-2"></i>
                        <a href="mailto:upabahasa@upnvj.ac.id" class="text-light text-decoration-none">
                            upabahasa@upnvj.ac.id
                        </a>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-globe me-2"></i>
                        <a href="https://upnvj.ac.id" class="text-light text-decoration-none" target="_blank">
                            upnvj.ac.id
                        </a>
                    </div>
                </div>
                
                <!-- Operating Hours -->
                <div class="bg-primary bg-opacity-25 rounded p-3">
                    <h6 class="fw-bold mb-2">Jam Operasional</h6>
                    <small>
                        <strong>Senin - Jumat:</strong> 08:00 - 16:00 WIB<br>
                        <strong>Sabtu:</strong> 08:00 - 12:00 WIB<br>
                        <strong>Minggu:</strong> Libur
                    </small>
                </div>
            </div>
        </div>
        
        <hr class="my-4 border-secondary">
        
        <!-- Bottom Footer -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-light">
                    &copy; <?= date('Y') ?> UPA Bahasa UPNVJ. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="btn btn-primary position-fixed bottom-0 end-0 m-3 rounded-circle d-none" style="z-index: 1000; width: 50px; height: 50px;">
    <i class="bi bi-arrow-up"></i>
</button>

<!-- Scripts -->
<script>
// Back to top functionality
window.addEventListener('scroll', function() {
    const backToTop = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
        backToTop.classList.remove('d-none');
    } else {
        backToTop.classList.add('d-none');
    }
});

document.getElementById('backToTop').addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});

// Add loading state to buttons
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Memproses...';
                submitBtn.disabled = true;
                
                // Re-enable after 10 seconds as fallback
                setTimeout(function() {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    });
});
</script>