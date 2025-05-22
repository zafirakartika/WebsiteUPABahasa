<!-- pages/student/course-registration.php -->
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ../../pages/auth/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kursus Persiapan ELPT - UPA Bahasa UPNVJ</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/navigation.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-journal-bookmark me-2"></i>
                            Pendaftaran Kursus Persiapan ELPT
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Info Alert -->
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Informasi Kursus:</strong><br>
                            • Program kursus terdiri dari 24 sesi (22 pembelajaran + 2 tes ELPT)<br>
                            • Durasi: 2.5 jam per sesi<br>
                            • Biaya: Rp. 2.500.000 (sudah termasuk tes ELPT final)<br>
                            • Sertifikat akan diberikan setelah menyelesaikan seluruh sesi
                        </div>

                        <!-- Course Registration Form -->
                        <form id="courseRegistrationForm">
                            <!-- Student Info (Read-only) -->
                            <div class="mb-4">
                                <h5>Data Mahasiswa</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" class="form-control" id="studentName" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">NIM</label>
                                            <input type="text" class="form-control" id="studentNim" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Program Studi</label>
                                            <input type="text" class="form-control" id="studentProgram" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Fakultas</label>
                                            <input type="text" class="form-control" id="studentFaculty" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Final Test Date Selection -->
                            <div class="mb-4">
                                <h5>Jadwal Tes Final</h5>
                                <div class="mb-3">
                                    <label class="form-label">Pilih Jadwal Tes ELPT Final *</label>
                                    <div id="finalTestDates" class="row">
                                        <!-- Dates will be loaded here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">syarat dan ketentuan</a> kursus persiapan ELPT
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Daftar Kursus
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Syarat dan Ketentuan Kursus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Ketentuan Umum</h6>
                    <ul>
                        <li>Kursus terdiri dari 24 sesi dengan durasi 2.5 jam per sesi</li>
                        <li>Peserta wajib hadir minimal 20 dari 22 sesi pembelajaran</li>
                        <li>Keterlambatan maksimal 15 menit dari waktu yang ditentukan</li>
                    </ul>

                    <h6>2. Pembayaran</h6>
                    <ul>
                        <li>Biaya kursus sebesar Rp. 2.500.000 dibayar sebelum memulai sesi pertama</li>
                        <li>Biaya sudah termasuk materi pembelajaran dan tes ELPT final</li>
                        <li>Pembayaran dapat dilakukan via transfer bank atau tunai</li>
                    </ul>

                    <h6>3. Tes Final</h6>
                    <ul>
                        <li>Tes ELPT final akan dilaksanakan sesuai jadwal yang dipilih</li>
                        <li>Peserta yang tidak hadir pada tes final dapat mengikuti tes susulan dengan biaya tambahan</li>
                        <li>Sertifikat diberikan setelah menyelesaikan seluruh program</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/bootstrap.min.js"></script>
    <script src="../../assets/js/sweetalert2.min.js"></script>
    <script>
    $(document).ready(function() {
        loadStudentData();
        loadAvailableDates();
        
        $('#courseRegistrationForm').on('submit', function(e) {
            e.preventDefault();
            registerCourse();
        });
    });

    function loadStudentData() {
        const user = JSON.parse(localStorage.getItem('user'));
        if (user) {
            $('#studentName').val(user.name);
            $('#studentNim').val(user.nim);
            $('#studentProgram').val(user.program);
            $('#studentFaculty').val(user.faculty);
        }
    }

    function loadAvailableDates() {
        $.ajax({
            url: '../../api/course/available-dates.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayAvailableDates(response.dates);
                } else {
                    showAlert('error', 'Gagal memuat jadwal: ' + response.message);
                }
            },
            error: function() {
                showAlert('error', 'Gagal memuat jadwal tes final');
            }
        });
    }

    function displayAvailableDates(dates) {
        const container = $('#finalTestDates');
        container.empty();
        
        if (dates.length === 0) {
            container.html('<p class="text-muted">Tidak ada jadwal tersedia saat ini.</p>');
            return;
        }
        
        dates.forEach(function(date) {
            const isAvailable = date.available && date.slots > 0;
            const cardClass = isAvailable ? 'border-primary' : 'border-secondary';
            const disabledClass = isAvailable ? '' : 'disabled';
            
            const html = `
                <div class="col-md-6 mb-3">
                    <div class="card ${cardClass} ${disabledClass}">
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="finalTestDate" 
                                       value="${date.datetime}" id="date_${date.datetime.replace(/[:\s-]/g, '_')}"
                                       ${isAvailable ? '' : 'disabled'} required>
                                <label class="form-check-label" for="date_${date.datetime.replace(/[:\s-]/g, '_')}">
                                    <strong>${date.display}</strong><br>
                                    <small class="text-muted">
                                        <i class="bi bi-people me-1"></i>
                                        ${isAvailable ? date.slots + ' slot tersisa' : 'Penuh'}
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
        });
    }

    function registerCourse() {
        const user = JSON.parse(localStorage.getItem('user'));
        const finalTestDate = $('input[name="finalTestDate"]:checked').val();
        
        if (!finalTestDate) {
            showAlert('warning', 'Silakan pilih jadwal tes final');
            return;
        }
        
        $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i>Memproses...');
        
        $.ajax({
            url: '../../api/course/register.php',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify({
                user_id: user.id,
                final_test_date: finalTestDate
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pendaftaran Berhasil!',
                        text: 'Anda berhasil mendaftar kursus persiapan ELPT. Silakan lakukan pembayaran untuk mengkonfirmasi pendaftaran.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Terjadi kesalahan. Silakan coba lagi.');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-2"></i>Daftar Kursus');
            }
        });
    }

    function showAlert(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Berhasil!' : type === 'error' ? 'Error!' : 'Peringatan!',
            text: message,
            confirmButtonText: 'OK'
        });
    }
    </script>
</body>
</html>

<!-- pages/student/course-dashboard.php -->
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ../../pages/auth/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kursus - UPA Bahasa UPNVJ</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/navigation.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8">
                <!-- Course Progress Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-journal-bookmark me-2"></i>
                            Progress Kursus Persiapan ELPT
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="courseProgress">
                            <!-- Course progress will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Session History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Riwayat Sesi Pembelajaran
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="sessionHistory">
                            <!-- Session history will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Course Info Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Informasi Kursus
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="courseInfo">
                            <!-- Course info will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>
                            Menu Cepat
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-outline-primary" id="markAttendanceBtn">
                                <i class="bi bi-check2-square me-2"></i>
                                Tandai Kehadiran
                            </a>
                            <a href="../student/test-results.php" class="btn btn-outline-info">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Lihat Hasil Tes
                            </a>
                            <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#helpModal">
                                <i class="bi bi-question-circle me-2"></i>
                                Bantuan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bantuan Kursus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Frequently Asked Questions</h6>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Bagaimana cara menandai kehadiran?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Anda dapat menandai kehadiran dengan mengklik tombol "Tandai Kehadiran" pada hari dan jam kursus yang sesuai.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Berapa minimal kehadiran yang diperlukan?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Minimal kehadiran adalah 20 dari 22 sesi pembelajaran untuk dapat mengikuti tes final.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Kapan jadwal tes final saya?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Jadwal tes final Anda dapat dilihat pada kartu informasi kursus di sebelah kanan halaman ini.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a href="mailto:upabahasa@upnvj.ac.id" class="btn btn-primary">Hubungi Admin</a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/bootstrap.min.js"></script>
    <script src="../../assets/js/sweetalert2.min.js"></script>
    <script>
    $(document).ready(function() {
        loadCourseData();
        
        $('#markAttendanceBtn').on('click', function(e) {
            e.preventDefault();
            markAttendance();
        });
    });

    function loadCourseData() {
        const user = JSON.parse(localStorage.getItem('user'));
        
        $.ajax({
            url: '../../api/course/sessions.php',
            type: 'GET',
            data: { user_id: user.id },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.course) {
                    displayCourseProgress(response.course);
                    displayCourseInfo(response.course);
                    generateSessionHistory(response.course);
                } else {
                    $('#courseProgress').html('<p class="text-muted">Tidak ada data kursus ditemukan.</p>');
                }
            },
            error: function() {
                $('#courseProgress').html('<p class="text-danger">Gagal memuat data kursus.</p>');
            }
        });
    }

    function displayCourseProgress(course) {
        const progressPercentage = (course.current_session / course.total_sessions) * 100;
        const statusBadge = getStatusBadge(course.status);
        
        const html = `
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6>Sesi ${course.current_session} dari ${course.total_sessions}</h6>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar" role="progressbar" style="width: ${progressPercentage}%">
                            ${Math.round(progressPercentage)}%
                        </div>
                    </div>
                    <p class="mb-0">Status: ${statusBadge}</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="display-6 text-primary">${course.current_session}</div>
                    <small class="text-muted">Sesi Saat Ini</small>
                </div>
            </div>
        `;
        
        $('#courseProgress').html(html);
    }

    function displayCourseInfo(course) {
        const finalTestDate = new Date(course.final_test_date);
        const formattedDate = finalTestDate.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        const formattedTime = finalTestDate.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const html = `
            <div class="info-item mb-3">
                <i class="bi bi-calendar-event text-primary me-2"></i>
                <strong>Tes Final:</strong><br>
                <small>${formattedDate}<br>${formattedTime}</small>
            </div>
            <div class="info-item mb-3">
                <i class="bi bi-graph-up text-success me-2"></i>
                <strong>Progress:</strong><br>
                <small>${course.current_session}/${course.total_sessions} sesi</small>
            </div>
            <div class="info-item mb-3">
                <i class="bi bi-award text-warning me-2"></i>
                <strong>Status:</strong><br>
                <small>${getStatusText(course.status)}</small>
            </div>
            <div class="info-item">
                <i class="bi bi-clock text-info me-2"></i>
                <strong>Durasi:</strong><br>
                <small>2.5 jam per sesi</small>
            </div>
        `;
        
        $('#courseInfo').html(html);
    }

    function generateSessionHistory(course) {
        let html = '<div class="timeline">';
        
        for (let i = 1; i <= course.total_sessions; i++) {
            const isCompleted = i <= course.current_session;
            const isCurrent = i === course.current_session + 1;
            const sessionType = i > 22 ? 'Tes ELPT' : 'Pembelajaran';
            
            let statusIcon, statusClass, statusText;
            
            if (isCompleted) {
                statusIcon = 'bi-check-circle-fill';
                statusClass = 'text-success';
                statusText = 'Selesai';
            } else if (isCurrent) {
                statusIcon = 'bi-arrow-right-circle';
                statusClass = 'text-primary';
                statusText = 'Sesi Berikutnya';
            } else {
                statusIcon = 'bi-circle';
                statusClass = 'text-muted';
                statusText = 'Belum Dimulai';
            }
            
            html += `
                <div class="timeline-item ${statusClass}">
                    <div class="timeline-marker">
                        <i class="bi ${statusIcon}"></i>
                    </div>
                    <div class="timeline-content">
                        <h6 class="mb-1">Sesi ${i} - ${sessionType}</h6>
                        <small>${statusText}</small>
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        $('#sessionHistory').html(html);
    }

    function getStatusBadge(status) {
        switch (status) {
            case 'active':
                return '<span class="badge bg-success">Aktif</span>';
            case 'completed':
                return '<span class="badge bg-primary">Selesai</span>';
            case 'pending':
                return '<span class="badge bg-warning">Menunggu Pembayaran</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    function getStatusText(status) {
        switch (status) {
            case 'active':
                return 'Kursus sedang berlangsung';
            case 'completed':
                return 'Kursus telah selesai';
            case 'pending':
                return 'Menunggu konfirmasi pembayaran';
            default:
                return 'Status tidak diketahui';
        }
    }

    function markAttendance() {
        const user = JSON.parse(localStorage.getItem('user'));
        
        Swal.fire({
            title: 'Konfirmasi Kehadiran',
            text: 'Apakah Anda hadir pada sesi hari ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hadir',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // In a real implementation, this would make an API call
                // to mark attendance and update session progress
                
                Swal.fire({
                    icon: 'success',
                    title: 'Kehadiran Tercatat!',
                    text: 'Terima kasih telah mengikuti sesi hari ini.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Refresh the page to update progress
                    location.reload();
                });
            }
        });
    }
    </script>
</body>
</html>