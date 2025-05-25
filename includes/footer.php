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
                <p class="text-light">
                    Unit Pelayanan Akademik Bahasa Universitas Pembangunan Nasional Veteran Jakarta.
                    Meningkatkan kemampuan bahasa Inggris mahasiswa melalui tes ELPT dan kursus persiapan.
                </p>
                <div class="d-flex gap-3">
                    <a href="https://www.instagram.com/upnveteranjakarta/" class="text-light">
                        <i class="bi bi-instagram" style="font-size: 1.5rem;"></i>
                    </a>
                    <a href="https://www.facebook.com/upnvj.official" class="text-light">
                        <i class="bi bi-facebook" style="font-size: 1.5rem;"></i>
                    </a>
                    <a href="https://twitter.com/upnvj_official" class="text-light">
                        <i class="bi bi-twitter" style="font-size: 1.5rem;"></i>
                    </a>
                    <a href="https://www.youtube.com/upnvjofficial" class="text-light">
                        <i class="bi bi-youtube" style="font-size: 1.5rem;"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3">Layanan</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="/student/elpt-registration.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>Tes ELPT
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/student/course.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>Kursus Persiapan
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/student/test-results.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>Hasil Tes
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/about.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>Tentang Kami
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Information -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3">Informasi</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="/faq.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>FAQ
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/schedule.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>Jadwal Tes
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/requirements.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>Persyaratan
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/contact.php" class="text-light text-decoration-none">
                            <i class="bi bi-arrow-right me-2"></i>Kontak
                        </a>
                    </li>
                </ul>
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
            <div class="col-md-6 text-md-end">
                <div class="d-flex justify-content-md-end gap-4 mt-3 mt-md-0">
                    <a href="/privacy.php" class="text-light text-decoration-none small">
                        Privacy Policy
                    </a>
                    <a href="/terms.php" class="text-light text-decoration-none small">
                        Terms of Service
                    </a>
                    <a href="/sitemap.php" class="text-light text-decoration-none small">
                        Sitemap
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Version Info -->
        <div class="text-center mt-3">
            <small class="text-muted">
                UPA Bahasa Management System v1.0 | Powered by UPNVJ IT Team
            </small>
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