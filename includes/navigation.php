<?php
// includes/navigation.php
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="<?= isset($_SESSION['user_id']) ? ($_SESSION['user_role'] === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php') : '/index.php' ?>">
            <i class="bi bi-mortarboard me-2"></i>
            UPA Bahasa UPNVJ
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Logged In Navigation -->
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <!-- Admin Navigation -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/registrations.php">
                                <i class="bi bi-calendar-event me-1"></i>Pendaftaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/payments.php">
                                <i class="bi bi-credit-card me-1"></i>Pembayaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/test-results.php">
                                <i class="bi bi-file-earmark-text me-1"></i>Hasil Tes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/students.php">
                                <i class="bi bi-people me-1"></i>Mahasiswa
                            </a>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- Student Navigation -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/student/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/student/elpt-registration.php">
                                <i class="bi bi-calendar-plus me-1"></i>Daftar ELPT
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/student/test-results.php">
                                <i class="bi bi-file-earmark-text me-1"></i>Hasil Tes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/student/course.php">
                                <i class="bi bi-book me-1"></i>Kursus
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
                
                <!-- User Menu -->
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center me-2" 
                                 style="width: 32px; height: 32px; font-size: 0.8rem; color: white;">
                                <?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?>
                            </div>
                            <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text">
                                    <small class="text-muted">
                                        <?= htmlspecialchars($_SESSION['user_email']) ?><br>
                                        <?= ucfirst($_SESSION['user_role']) ?>
                                    </small>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/profile.php">
                                    <i class="bi bi-person me-2"></i>Profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/settings.php">
                                    <i class="bi bi-gear me-2"></i>Pengaturan
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <!-- Guest Navigation -->
                <div class="navbar-nav ms-auto">
                    <a href="/login.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                    <a href="/register.php" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i>Daftar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Page Loading Overlay -->
<div id="pageLoader" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex align-items-center justify-content-center" style="z-index: 9999;">
    <div class="text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2">Memuat...</div>
    </div>
</div>