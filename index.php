<?php
require_once 'api/config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPA Bahasa UPNVJ - Unit Pelayanan Akademik Bahasa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(13, 110, 253, 0.8), rgba(13, 110, 253, 0.8)), url('https://upabahasa.upnvj.ac.id/wp-content/uploads/2023/11/WhatsApp-Image-2023-10-19-at-10.29.39-1.jpeg') center/cover;
            color: white;
            min-height: 70vh;
        }
        .feature-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .schedule-item {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="bi bi-mortarboard me-2"></i>UPA Bahasa UPNVJ
            </a>
            
            <div class="navbar-nav ms-auto">
                <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                <a href="register.php" class="btn btn-primary">Daftar</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">Selamat Datang di UPA Bahasa UPNVJ</h1>
                    <p class="lead mb-4">
                        Unit Pelayanan Akademik Bahasa Universitas Pembangunan Nasional Veteran Jakarta.
                        Tingkatkan kemampuan bahasa Inggris Anda dengan layanan ELPT dan kursus persiapan terbaik.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="register.php" class="btn btn-warning btn-lg px-4">
                            <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Layanan Kami</h2>
                <p class="text-muted">UPA Bahasa UPNVJ menyediakan berbagai layanan untuk meningkatkan kemampuan bahasa Inggris Anda</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-award text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5>English Language Proficiency Test (ELPT)</h5>
                        <p class="text-muted">Tes kemampuan bahasa Inggris yang diakui secara internal UPNVJ untuk keperluan akademik.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="bi bi-check-circle text-success me-2"></i>Listening Comprehension</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Structure & Written Expression</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Reading Comprehension</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-book text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5>Kursus Persiapan ELPT</h5>
                        <p class="text-muted">Program intensif 24 sesi untuk mempersiapkan Anda menghadapi tes ELPT dengan hasil optimal.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="bi bi-check-circle text-success me-2"></i>22 Sesi Pembelajaran</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>2 Sesi Tes ELPT</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Instruktur Berpengalaman</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-calendar-check text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5>Jadwal Fleksibel</h5>
                        <p class="text-muted">Pilih jadwal tes yang sesuai dengan kebutuhan Anda dari berbagai pilihan waktu yang tersedia.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="bi bi-clock text-info me-2"></i>Selasa: 09:30-12:00 & 13:00-15:30</li>
                            <li><i class="bi bi-clock text-info me-2"></i>Kamis: 09:30-12:00 & 13:00-15:30</li>
                            <li><i class="bi bi-clock text-info me-2"></i>Sabtu: 07:00-09:30, 09:30-12:00 & 13:00-15:30</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Jadwal Tes ELPT Regular</h2>
                <p class="text-muted">Tes ELPT diselenggarakan tiga kali seminggu dengan kapasitas maksimal 30 peserta per sesi</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="schedule-item p-4 rounded h-100">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-day text-primary me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h5 class="mb-0">Selasa</h5>
                                <small class="text-muted">Sesi Pagi & Siang</small>
                            </div>
                        </div>
                        <p class="mb-2"><strong>09:30 - 12:00</strong> (Sesi Pagi)</p>
                        <p class="mb-0"><strong>13:00 - 15:30</strong> (Sesi Siang)</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="schedule-item p-4 rounded h-100">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-day text-primary me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h5 class="mb-0">Kamis</h5>
                                <small class="text-muted">Sesi Pagi & Siang</small>
                            </div>
                        </div>
                        <p class="mb-2"><strong>09:30 - 12:00</strong> (Sesi Pagi)</p>
                        <p class="mb-0"><strong>13:00 - 15:30</strong> (Sesi Siang)</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="schedule-item p-4 rounded h-100">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-day text-primary me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h5 class="mb-0">Sabtu</h5>
                                <small class="text-muted">Sesi Pagi, Siang & Sore</small>
                            </div>
                        </div>
                        <p class="mb-1"><strong>07:00 - 09:30</strong> (Sesi Pagi)</p>
                        <p class="mb-1"><strong>09:30 - 12:00</strong> (Sesi Siang)</p>
                        <p class="mb-0"><strong>13:00 - 15:30</strong> (Sesi Sore)</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Kuota maksimal: 30 peserta per sesi. Pendaftaran minimal H+1 dari tanggal daftar.
                </p>
                <a href="register.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-calendar-plus me-2"></i>Daftar Tes ELPT Sekarang
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Mengapa Memilih UPA Bahasa UPNVJ?</h2>
                <p class="text-muted">Keunggulan layanan kami untuk kemajuan akademik Anda</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-patch-check text-success me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h5>Terintegrasi dengan SIAKAD</h5>
                            <p class="text-muted">Data mahasiswa terintegrasi langsung dengan sistem akademik UPNVJ untuk validasi otomatis.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-award text-primary me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h5>Sertifikat Diakui</h5>
                            <p class="text-muted">Sertifikat ELPT diakui secara internal UPNVJ untuk keperluan skripsi, yudisium, dan kelulusan.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-people text-warning me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h5>Instruktur Berpengalaman</h5>
                            <p class="text-muted">Didukung oleh instruktur yang berpengalaman dan memiliki sertifikasi internasional.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-graph-up text-info me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h5>Sistem Monitoring</h5>
                            <p class="text-muted">Pantau progress hasil tes dan perkembangan kemampuan bahasa Inggris Anda secara real-time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="fw-bold mb-3">Siap Meningkatkan Kemampuan Bahasa Inggris Anda?</h2>
            <p class="lead mb-4">Bergabunglah dengan ribuan mahasiswa UPNVJ yang telah meningkatkan kemampuan bahasa Inggris mereka</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="register.php" class="btn btn-warning btn-lg px-4">
                    <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sudah Punya Akun?
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5><i class="bi bi-mortarboard me-2"></i>UPA Bahasa UPNVJ</h5>
                    <p class="text-light">Unit Pelayanan Akademik Bahasa Universitas Pembangunan Nasional Veteran Jakarta</p>
                </div>
                
                <div class="col-md-4">
                    <h6>Kontak</h6>
                    <p class="mb-1"><i class="bi bi-geo-alt me-2"></i>Jl. RS. Fatmawati No.1, Jakarta Selatan</p>
                    <p class="mb-1"><i class="bi bi-telephone me-2"></i>(021) 7656971</p>
                    <p class="mb-0"><i class="bi bi-envelope me-2"></i>upabahasa@upnvj.ac.id</p>
                </div>
                
                <div class="col-md-4">
                    <h6>Jam Operasional</h6>
                    <p class="mb-1">Senin - Jumat: 08:00 - 16:00 WIB</p>
                    <p class="mb-1">Sabtu: 08:00 - 12:00 WIB</p>
                    <p class="mb-0">Minggu: Libur</p>
                </div>
            </div>
            
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> UPA Bahasa UPNVJ. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>