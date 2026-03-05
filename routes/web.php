<?php

use App\Controllers\Absensi;
use App\Controllers\AdminAbsensi;
use App\Controllers\AdminAbsensiDobel;
use App\Controllers\AdminExport;
use App\Controllers\AdminFotoKegiatan;
use App\Controllers\AdminGuru;
use App\Controllers\AdminMateri;
use App\Controllers\AdminMurid;
use App\Controllers\AdminNaikKelas;
use App\Controllers\Auth;
use App\Controllers\AuthForgot;
use App\Controllers\AuthOtp;
use App\Controllers\AuthRegister;
use App\Controllers\Dashboard;
use App\Controllers\GuruKegiatan;
use App\Controllers\GuruMateri;
use App\Controllers\GuruMurid;
use App\Controllers\Home;
use App\Controllers\Offline;
use App\Controllers\Admin\AuditLog;
use App\Controllers\Admin\Profil as AdminProfil;
use App\Controllers\Admin\RankingMurid;
use App\Controllers\Admin\Statistik;
use App\Controllers\Guru\Dashboard as GuruDashboard;
use App\Controllers\Guru\Profil as GuruProfil;
use App\Controllers\Superadmin\ActivityLog;
use App\Controllers\Superadmin\Dashboard as SuperadminDashboard;
use App\Controllers\Superadmin\Log as SuperadminLog;
use App\Controllers\Superadmin\Monitoring;
use App\Controllers\Superadmin\NaikKelas as SuperadminNaikKelas;
use App\Controllers\Superadmin\SystemControl;
use App\Controllers\Superadmin\SystemLog;
use App\Controllers\Superadmin\TahunAjaran;
use App\Controllers\Superadmin\Tingkat;
use App\Controllers\Superadmin\UserRole;
use App\Controllers\Superadmin\WaToken as SuperadminWaToken;
use App\Controllers\Superadmin\WaTemplate as SuperadminWaTemplate;
use Illuminate\Support\Facades\Route;

Route::get('/laravel-presensi/public', static fn () => redirect('/'));

Route::get('/', [Home::class, 'index']);

Route::get('/login', [Auth::class, 'login']);
Route::post('/login', [Auth::class, 'attemptLogin']);
Route::get('/logout', [Auth::class, 'logout']);

Route::get('/register-guru', [AuthRegister::class, 'form']);
Route::post('/register-guru', [AuthRegister::class, 'store']);
Route::post('/ajax/check-user', [AuthRegister::class, 'checkUser']);
Route::get('/register-pending', [AuthRegister::class, 'pending']);

Route::get('/forgot', [AuthForgot::class, 'index']);
Route::post('/forgot/email', [AuthForgot::class, 'email']);
Route::post('/forgot/wa', [AuthForgot::class, 'wa']);
Route::get('/reset-password-email', [AuthForgot::class, 'resetFormEmail']);
Route::post('/reset-password-email', [AuthForgot::class, 'resetSaveEmail']);

Route::get('/verify-otp', [AuthOtp::class, 'form']);
Route::post('/verify-otp', [AuthOtp::class, 'verify']);
Route::get('/reset-password-wa', [AuthOtp::class, 'resetForm']);
Route::post('/reset-password-wa', [AuthOtp::class, 'resetSave']);

Route::middleware('auth')->prefix('dashboard')->group(function (): void {
    Route::get('/', [Dashboard::class, 'index']);
    Route::get('/superadmin', [Dashboard::class, 'superadmin'])->middleware('role:1');
    Route::get('/admin', [Dashboard::class, 'admin'])->middleware('role:2');
    Route::get('/guru', [Dashboard::class, 'guru'])->middleware('role:3');
});

Route::prefix('dashboard/superadmin')->middleware(['auth', 'role:1'])->group(function (): void {
    Route::get('/', [Dashboard::class, 'superadmin']);
    Route::post('/action', [SuperadminDashboard::class, 'action']);

    Route::get('/guru', [AdminGuru::class, 'index']);
    Route::get('/guru/create', [AdminGuru::class, 'create']);
    Route::post('/guru/store', [AdminGuru::class, 'store']);
    Route::get('/guru/detail/{id}', [AdminGuru::class, 'detail']);
    Route::get('/guru/toggle/{id}', [AdminGuru::class, 'toggle']);
    Route::post('/guru/delete/{id}', [AdminGuru::class, 'delete']);

    Route::get('/murid', [AdminMurid::class, 'index']);
    Route::get('/kelas', [Tingkat::class, 'index']);

    Route::get('/rekap-absensi', [AdminAbsensi::class, 'index']);
    Route::get('/statistik-absensi', [Statistik::class, 'index']);
    Route::get('/absensi-dobel', [AdminAbsensiDobel::class, 'index']);

    Route::get('/materi', [AdminMateri::class, 'index']);
    Route::get('/naik-kelas', [AdminNaikKelas::class, 'preview']);

    Route::get('/export-excel/mingguan', [AdminExport::class, 'mingguan']);
    Route::get('/export-excel/bulanan', [AdminExport::class, 'bulanan']);
    Route::get('/export-excel/tahunan', [AdminExport::class, 'tahunan']);

    Route::get('/audit-log', [AuditLog::class, 'index']);
    Route::get('/audit-log/{id}', [AuditLog::class, 'detail']);

    Route::get('/profil', [AdminProfil::class, 'index']);
    Route::post('/profil/update', [AdminProfil::class, 'update']);
});

Route::prefix('admin')->middleware(['auth', 'role:2', 'menuaccess'])->group(function (): void {
    Route::get('/guru/online-json', [Dashboard::class, 'guruOnlineJson']);
    Route::get('/guru/notif-count', [AdminGuru::class, 'notifCount']);
    Route::get('/guru', [AdminGuru::class, 'index']);
    Route::get('/guru/create', [AdminGuru::class, 'create']);
    Route::post('/guru/store', [AdminGuru::class, 'store']);
    Route::get('/guru/detail/{id}', [AdminGuru::class, 'detail']);
    Route::get('/guru/toggle/{id}', [AdminGuru::class, 'toggle']);
    Route::post('/guru/delete/{id}', [AdminGuru::class, 'delete']);
    Route::get('/guru/toggle-role/{id}', [AdminGuru::class, 'toggleRole']);

    Route::get('/rekap-absensi', [AdminAbsensi::class, 'index']);
    Route::get('/rekap-absensi/range', [AdminAbsensi::class, 'range']);
    Route::get('/rekap-absensi/detail/{tanggal}', [AdminAbsensi::class, 'detailTanggal']);
    Route::get('/rekap-absensi/kelas', [AdminAbsensi::class, 'kelas']);
    Route::get('/rekap-absensi/kelas-detail', [AdminAbsensi::class, 'kelasDetail']);
    Route::get('/rekap-absensi/export/{mode}/{tanggal}', [AdminAbsensi::class, 'export']);

    Route::get('/statistik-absensi', [Statistik::class, 'index']);
    Route::get('/statistik', [Statistik::class, 'index']);

    Route::get('/laporan-point', [RankingMurid::class, 'index']);
    Route::get('/laporan-point/export-excel', [RankingMurid::class, 'exportPdf']);
    Route::get('/laporan-point/export-pdf', [RankingMurid::class, 'exportPdf']);

    Route::get('/export-excel/mingguan', [AdminExport::class, 'mingguan']);
    Route::get('/export-excel/bulanan', [AdminExport::class, 'bulanan']);
    Route::get('/export-excel/tahunan', [AdminExport::class, 'tahunan']);

    Route::get('/bahan-ajar', [AdminMateri::class, 'index']);
    Route::get('/bahan-ajar/fetch', [AdminMateri::class, 'fetch']);
    Route::post('/bahan-ajar/upload', [AdminMateri::class, 'upload']);
    Route::post('/bahan-ajar/update-ajax/{id}', [AdminMateri::class, 'updateAjax']);
    Route::post('/bahan-ajar/delete-ajax/{id}', [AdminMateri::class, 'deleteAjax']);

    Route::get('/murid/import', [AdminMurid::class, 'importForm']);
    Route::post('/murid/import-preview', [AdminMurid::class, 'importPreview']);
    Route::post('/murid/import-execute', [AdminMurid::class, 'importExecute']);

    Route::get('/absensi-dobel', [AdminAbsensiDobel::class, 'index']);
    Route::post('/absensi-dobel/resolve', [AdminAbsensiDobel::class, 'resolve']);
    Route::get('/absensi-dobel/count', [AdminAbsensiDobel::class, 'count']);

    Route::get('/audit-log', [AuditLog::class, 'index']);
    Route::get('/audit-log/detail/{id}', [AuditLog::class, 'detail']);
    Route::get('/audit-log/export-pdf', [AuditLog::class, 'exportPdf']);

    Route::get('/naik-kelas', [AdminNaikKelas::class, 'preview']);
    Route::post('/naik-kelas/execute', [AdminNaikKelas::class, 'execute']);
    Route::post('/naik-kelas/undo', [AdminNaikKelas::class, 'undo']);
    Route::post('/naik-kelas/lock', [AdminNaikKelas::class, 'lock']);
    Route::get('/naik-kelas/histori', [AdminNaikKelas::class, 'histori']);
    Route::get('/naik-kelas/histori/export-csv', [AdminNaikKelas::class, 'exportCsv']);
    Route::get('/naik-kelas/histori/export-pdf', [AdminNaikKelas::class, 'exportPdf']);
    Route::get('/naik-kelas/histori/detail/{id}', [AdminNaikKelas::class, 'historiDetail']);
    Route::get('/naik-kelas/histori/export-csv/{id}', [AdminNaikKelas::class, 'exportSnapshotCsv']);
    Route::get('/naik-kelas/histori/export-pdf/{id}', [AdminNaikKelas::class, 'exportSnapshotPdf']);

    Route::get('/foto-kegiatan', [AdminFotoKegiatan::class, 'index']);

    Route::get('/profil', [AdminProfil::class, 'index']);
    Route::post('/profil/update', [AdminProfil::class, 'update']);

    Route::get('/ranking-murid', [RankingMurid::class, 'index']);
    Route::get('/ranking-murid/export-pdf', [RankingMurid::class, 'exportPdf']);
});

Route::prefix('guru')->middleware(['auth', 'role:3', 'menuaccess'])->group(function (): void {
    Route::get('/status', [GuruDashboard::class, 'ajaxStatus']);

    Route::get('/absensi', [Absensi::class, 'step1']);
    Route::get('/absensi/tampilkan', [Absensi::class, 'tampilkan']);
    Route::match(['get', 'post'], '/absensi/simpan', [Absensi::class, 'simpan']);
    Route::get('/absensi/dobel', [Absensi::class, 'dobel']);
    Route::get('/unity', [Absensi::class, 'unityStep1']);
    Route::get('/unity/tampilkan', [Absensi::class, 'unityTampilkan']);
    Route::match(['get', 'post'], '/unity/simpan', [Absensi::class, 'simpanUnity']);

    Route::get('/absensi-hari-ini', [Absensi::class, 'hariIni']);
    Route::post('/absensi-hari-ini/simpan', [Absensi::class, 'simpanEditHariIni']);

    Route::get('/murid', [GuruMurid::class, 'index']);
    Route::get('/murid/create', [GuruMurid::class, 'create']);
    Route::post('/murid/store', [GuruMurid::class, 'store']);
    Route::get('/murid/edit/{id}', [GuruMurid::class, 'edit']);
    Route::post('/murid/update/{id}', [GuruMurid::class, 'update']);

    Route::get('/materi', [GuruMateri::class, 'index']);
    Route::get('/materi/ajax/{id}', [GuruMateri::class, 'ajax']);
    Route::get('/materi/download/{id}', [GuruMateri::class, 'download']);

    Route::get('/kegiatan', [GuruKegiatan::class, 'index']);
    Route::post('/kegiatan/store', [GuruKegiatan::class, 'store']);

    Route::get('/profil', [GuruProfil::class, 'index']);
    Route::post('/profil/update', [GuruProfil::class, 'update']);

    Route::get('/audit-log', static fn () => redirect('/dashboard/guru'));
});

Route::get('/offline', [Offline::class, 'index']);

Route::prefix('superadmin')->middleware(['auth', 'role:1'])->group(function (): void {
    Route::get('/', [SuperadminDashboard::class, 'index']);
    Route::get('/dashboard', [SuperadminDashboard::class, 'index']);

    Route::get('/log', [SuperadminLog::class, 'index']);
    Route::get('/monitoring', [Monitoring::class, 'index']);
    Route::get('/wa-template', [SuperadminWaTemplate::class, 'index']);
    Route::post('/wa-template/save', [SuperadminWaTemplate::class, 'save']);
    Route::get('/wa-token', [SuperadminWaToken::class, 'index']);
    Route::post('/wa-token/save', [SuperadminWaToken::class, 'save']);

    Route::get('/users', [UserRole::class, 'index']);
    Route::post('/users/update', [UserRole::class, 'update']);

    Route::get('/tingkat', [Tingkat::class, 'index']);
    Route::post('/tingkat/store', [Tingkat::class, 'store']);
    Route::post('/tingkat/delete/{id}', [Tingkat::class, 'delete']);

    Route::get('/tahun-ajaran', [TahunAjaran::class, 'index']);
    Route::post('/tahun-ajaran/store', [TahunAjaran::class, 'store']);
    Route::post('/tahun-ajaran/activate/{id}', [TahunAjaran::class, 'activate']);

    Route::get('/naik-kelas', [SuperadminNaikKelas::class, 'index']);
    Route::post('/naik-kelas/proses', [SuperadminNaikKelas::class, 'proses']);

    Route::get('/system-log', [SystemLog::class, 'index']);
    Route::get('/system-control', [SystemControl::class, 'index']);
    Route::get('/system-control/toggle-maintenance', [SystemControl::class, 'toggleMaintenance']);
    Route::get('/system-control/toggle-absensi', [SystemControl::class, 'toggleAbsensi']);
    Route::post('/system-control/toggle-menu/{menu}', [SystemControl::class, 'toggleMenu']);
    Route::get('/activity-log', [ActivityLog::class, 'index']);
});

Route::get('/dashboard/profil', static fn () => redirect(profile_url()));
Route::get('/guru/dashboard', static fn () => redirect('/dashboard/guru'));
