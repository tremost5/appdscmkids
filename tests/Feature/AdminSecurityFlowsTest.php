<?php

namespace Tests\Feature;

use App\Controllers\AdminAbsensi;
use App\Controllers\AdminAbsensiDobel;
use App\Controllers\Admin\AuditLog;
use App\Controllers\AdminExport;
use App\Controllers\AdminFotoKegiatan;
use App\Controllers\AdminGuru;
use App\Controllers\AdminMateri;
use App\Controllers\AdminNaikKelas;
use App\Controllers\PwaPush;
use App\Controllers\Superadmin\UserRole;
use App\Services\PwaPushService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AdminSecurityFlowsTest extends TestCase
{
    private string $sqlitePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sqlitePath = storage_path('framework/testing/admin_security_test_' . bin2hex(random_bytes(4)) . '.sqlite');
        if (!is_dir(dirname($this->sqlitePath))) {
            mkdir(dirname($this->sqlitePath), 0775, true);
        }
        if (file_exists($this->sqlitePath)) {
            @unlink($this->sqlitePath);
        }
        touch($this->sqlitePath);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->sqlitePath,
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->resetCompatConnection();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->setUpSchema();
        $this->seedReferenceData();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        $this->resetCompatConnection();

        if (isset($this->sqlitePath) && file_exists($this->sqlitePath)) {
            @unlink($this->sqlitePath);
        }

        parent::tearDown();
    }

    public function test_admin_can_toggle_guru_status_via_post_ajax(): void
    {
        $admin = $this->createUser(2, 'Admin Test');
        $guru = $this->createUser(3, 'Guru Test');

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('POST', '/admin/guru/toggle/' . $guru['id'], ['_token' => 'test'], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $response = (new AdminGuru())->toggle($guru['id']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('nonaktif', $payload['status']);
        $this->assertSame('Status guru diperbarui', $payload['message']);
        $this->assertSame('nonaktif', DB::table('users')->where('id', $guru['id'])->value('status'));
    }

    public function test_rekap_detail_separates_reguler_and_unity_labels(): void
    {
        $admin = $this->createUser(2, 'Admin Rekap');
        $guru = $this->createUser(3, 'Guru Rekap');
        $kelasId = (int) DB::table('kelas')->orderBy('id')->value('id');
        $lokasiId = (int) DB::table('lokasi_ibadah')->orderBy('id')->value('id');
        $tanggal = '2026-03-08';

        $muridId = DB::table('murid')->insertGetId([
            'kelas_id' => $kelasId,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2015-01-01',
            'nama_depan' => 'Murid',
            'nama_belakang' => 'Rekap',
            'panggilan' => 'Murid',
            'unity' => 'Unity Peter',
            'alamat' => 'Test',
            'no_hp' => '620000000001',
            'foto' => 'default.png',
            'status' => 'aktif',
            'created_at' => now(),
        ]);

        $regulerAbsensiId = DB::table('absensi')->insertGetId([
            'guru_id' => $guru['id'],
            'lokasi_id' => $lokasiId,
            'lokasi_text' => 'NICC',
            'jenis_presensi' => 'reguler',
            'tanggal' => $tanggal,
            'jam' => '08:00:00',
            'created_at' => now(),
        ]);
        DB::table('absensi_detail')->insert([
            'absensi_id' => $regulerAbsensiId,
            'murid_id' => $muridId,
            'status' => 'hadir',
            'tanggal' => $tanggal,
            'created_at' => now(),
        ]);

        $unityAbsensiId = DB::table('absensi')->insertGetId([
            'guru_id' => $guru['id'],
            'lokasi_id' => $lokasiId,
            'lokasi_text' => 'NICC',
            'jenis_presensi' => 'unity',
            'tanggal' => $tanggal,
            'jam' => '09:00:00',
            'created_at' => now(),
        ]);
        DB::table('absensi_detail')->insert([
            'absensi_id' => $unityAbsensiId,
            'murid_id' => $muridId,
            'status' => 'hadir',
            'tanggal' => $tanggal,
            'created_at' => now(),
        ]);

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('GET', '/admin/rekap-absensi/detail/' . $tanggal, ['mode' => 'reguler']);
        $regulerHtml = (string) (new AdminAbsensi())->detailTanggal($tanggal);

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('GET', '/admin/rekap-absensi/detail/' . $tanggal, ['mode' => 'unity']);
        $unityHtml = (string) (new AdminAbsensi())->detailTanggal($tanggal);

        $this->assertStringContainsString('Rekap Presensi Reguler', $regulerHtml);
        $this->assertStringContainsString('Guru', $regulerHtml);
        $this->assertStringNotContainsString('Mentor', $regulerHtml);

        $this->assertStringContainsString('Rekap Presensi Unity', $unityHtml);
        $this->assertStringContainsString('Mentor', $unityHtml);
    }

    public function test_bahan_ajar_rejects_invalid_link_payload(): void
    {
        $admin = $this->createUser(2, 'Admin Materi');
        $kelasId = (int) DB::table('kelas')->orderBy('id')->value('id');

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('POST', '/admin/bahan-ajar/upload', [
            '_token' => 'test',
            'judul' => 'Materi Invalid',
            'catatan' => 'test',
            'kelas_id' => $kelasId,
            'kategori' => 'link',
            'link' => 'bukan-url-valid',
        ], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $response = (new AdminMateri())->upload();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('error', $payload['status']);
        $this->assertSame('Link wajib valid untuk kategori link', $payload['message']);
    }

    public function test_superadmin_cannot_demote_the_current_logged_in_account(): void
    {
        $superadmin = $this->createUser(1, 'Root Test');

        $this->bindSession($this->sessionPayload($superadmin));
        $this->bindRequest('POST', '/superadmin/users/update', [
            '_token' => 'test',
            'user_id' => $superadmin['id'],
            'role_id' => 2,
        ]);

        $response = (new UserRole())->update();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(1, (int) DB::table('users')->where('id', $superadmin['id'])->value('role_id'));
        $this->assertSame(
            'Tidak boleh menurunkan role akun superadmin yang sedang dipakai.',
            session()->get('error')
        );
    }

    public function test_resolve_dobel_only_updates_same_jenis_presensi_group(): void
    {
        $admin = $this->createUser(2, 'Admin Dobel');
        $guru = $this->createUser(3, 'Guru Dobel');
        $muridId = $this->createMurid();
        $lokasiId = 1;
        $tanggal = '2026-03-08';

        $absensiReg1 = $this->createAbsensi($guru['id'], $lokasiId, $tanggal, '08:00:00', 'reguler');
        $absensiReg2 = $this->createAbsensi($guru['id'], $lokasiId, $tanggal, '08:05:00', 'reguler');
        $absensiUnity = $this->createAbsensi($guru['id'], $lokasiId, $tanggal, '09:00:00', 'unity');

        $detailKeep = $this->createAbsensiDetail($absensiReg1, $muridId, 'dobel', $tanggal);
        $detailCancel = $this->createAbsensiDetail($absensiReg2, $muridId, 'dobel', $tanggal);
        $detailUnity = $this->createAbsensiDetail($absensiUnity, $muridId, 'hadir', $tanggal);

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('POST', '/admin/absensi-dobel/resolve', [
            'detail_id' => $detailKeep,
            'murid_id' => $muridId,
            'tanggal' => $tanggal,
        ], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $response = (new AdminAbsensiDobel())->resolve();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('ok', $response->getData(true)['status']);

        $this->assertSame('hadir', DB::table('absensi_detail')->where('id', $detailKeep)->value('status'));
        $this->assertSame('batal', DB::table('absensi_detail')->where('id', $detailCancel)->value('status'));
        $this->assertSame('hadir', DB::table('absensi_detail')->where('id', $detailUnity)->value('status'));
    }

    public function test_naik_kelas_lock_and_undo_restore_previous_snapshot(): void
    {
        $admin = $this->createUser(2, 'Admin Naik');
        DB::table('kelas')->insert([
            ['id' => 2, 'tingkat_id' => null, 'kode_kelas' => 'TKA', 'nama_kelas' => 'TKA'],
        ]);
        $muridId = DB::table('murid')->insertGetId([
            'kelas_id' => 2,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2015-01-01',
            'nama_depan' => 'Undo',
            'nama_belakang' => 'Murid',
            'panggilan' => 'Undo',
            'alamat' => 'Test',
            'no_hp' => '620000000002',
            'foto' => 'default.png',
            'status' => 'aktif',
            'created_at' => now(),
        ]);

        $historyId = DB::table('kelas_history')->insertGetId([
            'mode' => 'naik',
            'tahun_ajaran' => date('Y').'/'.(date('Y') + 1),
            'executed_at' => now(),
            'executed_by' => $admin['id'],
            'snapshot' => json_encode([['id' => $muridId, 'kelas_id' => 1]]),
            'is_locked' => 0,
        ]);

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('POST', '/admin/naik-kelas/lock', []);
        $lockResponse = (new AdminNaikKelas())->lock();
        $this->assertInstanceOf(RedirectResponse::class, $lockResponse);
        $this->assertSame(1, (int) DB::table('kelas_history')->where('id', $historyId)->value('is_locked'));

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('POST', '/admin/naik-kelas/undo', []);
        $undoResponse = (new AdminNaikKelas())->undo();
        $this->assertInstanceOf(RedirectResponse::class, $undoResponse);
        $this->assertSame(1, (int) DB::table('murid')->where('id', $muridId)->value('kelas_id'));
        $this->assertNull(DB::table('kelas_history')->where('id', $historyId)->value('id'));
    }

    public function test_audit_log_alert_filter_only_returns_warning_and_critical(): void
    {
        $admin = $this->createUser(2, 'Admin Audit');
        DB::table('audit_log')->insert([
            [
                'user_id' => $admin['id'],
                'role' => 'admin',
                'action' => 'info_action',
                'severity' => 'info',
                'created_at' => '2026-03-08 08:00:00',
            ],
            [
                'user_id' => $admin['id'],
                'role' => 'admin',
                'action' => 'warning_action',
                'severity' => 'warning',
                'created_at' => '2026-03-08 09:00:00',
            ],
        ]);

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('GET', '/admin/audit-log', [
            'start' => '2026-03-08',
            'end' => '2026-03-08',
            'alert' => '1',
        ]);
        $html = (string) (new AuditLog())->index();

        $this->assertStringContainsString('WARNING ACTION', $html);
        $this->assertStringNotContainsString('INFO ACTION', $html);
    }

    public function test_foto_kegiatan_filters_by_kelas_and_formats_location(): void
    {
        $admin = $this->createUser(2, 'Admin Foto');
        $guru = $this->createUser(3, 'Guru Foto');
        DB::table('kelas')->insert([
            ['id' => 2, 'tingkat_id' => null, 'kode_kelas' => 'TKA', 'nama_kelas' => 'TKA'],
        ]);
        $muridKelas1 = $this->createMurid(1, 'Foto', 'Satu');
        $muridKelas2 = $this->createMurid(2, 'Foto', 'Dua');

        $absensi1 = DB::table('absensi')->insertGetId([
            'guru_id' => $guru['id'],
            'lokasi_id' => 1,
            'lokasi_text' => 'NICC',
            'keterangan' => 'Cafe Test',
            'jenis_presensi' => 'unity',
            'tanggal' => '2026-03-08',
            'jam' => '10:00:00',
            'selfie_foto' => 'selfie1.jpg',
            'created_at' => now(),
        ]);
        DB::table('absensi_detail')->insert([
            'absensi_id' => $absensi1,
            'murid_id' => $muridKelas1,
            'status' => 'hadir',
            'tanggal' => '2026-03-08',
            'created_at' => now(),
        ]);

        $absensi2 = DB::table('absensi')->insertGetId([
            'guru_id' => $guru['id'],
            'lokasi_id' => 1,
            'lokasi_text' => 'NICC',
            'jenis_presensi' => 'reguler',
            'tanggal' => '2026-03-08',
            'jam' => '11:00:00',
            'selfie_foto' => 'selfie2.jpg',
            'created_at' => now(),
        ]);
        DB::table('absensi_detail')->insert([
            'absensi_id' => $absensi2,
            'murid_id' => $muridKelas2,
            'status' => 'hadir',
            'tanggal' => '2026-03-08',
            'created_at' => now(),
        ]);

        $this->bindSession($this->sessionPayload($admin));
        $this->bindRequest('GET', '/admin/foto-kegiatan', [
            'tanggal' => '2026-03-08',
            'kelas' => 1,
        ]);
        $html = (string) (new AdminFotoKegiatan())->index();

        $this->assertStringContainsString('Cafe Test', $html);
        $this->assertStringContainsString('selfie1.jpg', $html);
        $this->assertStringNotContainsString('selfie2.jpg', $html);
    }

    public function test_export_renderer_uses_guru_for_reguler_and_mentor_for_unity(): void
    {
        $controller = new AdminExport();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('renderExcelRows');
        $method->setAccessible(true);

        $reguler = $method->invoke($controller, [[
            'tanggal' => '2026-03-08',
            'nama_depan' => 'A',
            'nama_belakang' => 'B',
            'panggilan' => '',
            'nama_kelas' => 'PG',
            'jam' => '08:00:00',
            'nama_lokasi' => 'NICC',
            'keterangan' => null,
            'guru_depan' => 'Guru',
            'guru_belakang' => 'Satu',
            'jenis_presensi' => 'reguler',
        ]], 'reguler');

        $unity = $method->invoke($controller, [[
            'tanggal' => '2026-03-08',
            'nama_depan' => 'A',
            'nama_belakang' => 'B',
            'panggilan' => '',
            'nama_kelas' => 'PG',
            'jam' => '08:00:00',
            'nama_lokasi' => 'NICC',
            'keterangan' => null,
            'guru_depan' => 'Mentor',
            'guru_belakang' => 'Dua',
            'jenis_presensi' => 'unity',
        ]], 'unity');

        $this->assertStringContainsString("Tanggal\tNama\tKelas\tJam\tLokasi\tGuru", $reguler);
        $this->assertStringContainsString("Tanggal\tNama\tKelas\tJam\tLokasi\tMentor", $unity);
    }

    public function test_pwa_subscription_can_be_saved_for_logged_in_user(): void
    {
        $guru = $this->createUser(3, 'Guru Push');

        $this->bindSession($this->sessionPayload($guru));
        $this->bindRequest('POST', '/pwa/push/subscribe', [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'subscription' => [
                'endpoint' => 'https://push.example.test/sub-1',
                'keys' => [
                    'p256dh' => 'public-key-1',
                    'auth' => 'auth-key-1',
                ],
                'contentEncoding' => 'aes128gcm',
            ],
        ], JSON_UNESCAPED_SLASHES));

        $response = (new PwaPush())->subscribe(app(PwaPushService::class));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('pwa_subscriptions')->count());
        $this->assertSame('https://push.example.test/sub-1', DB::table('pwa_subscriptions')->value('endpoint'));
    }

    public function test_pwa_push_service_skips_sending_when_vapid_not_configured(): void
    {
        DB::table('pwa_subscriptions')->insert([
            'user_id' => 1,
            'role_id' => 3,
            'endpoint' => 'https://push.example.test/sub-skip',
            'public_key' => 'public-key-skip',
            'auth_token' => 'auth-key-skip',
            'content_encoding' => 'aes128gcm',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config([
            'pwa_push.enabled' => true,
            'pwa_push.public_key' => '',
            'pwa_push.private_key' => '',
            'pwa_push.subject' => 'mailto:test@example.com',
            'pwa_push.target_role_ids' => [3],
        ]);

        $result = app(PwaPushService::class)->sendNewMateriNotification([
            'id' => 99,
            'judul' => 'Materi Push',
            'kelas_id' => 1,
            'nama_kelas' => 'PG',
        ]);

        $this->assertSame(['sent' => 0, 'failed' => 0, 'skipped' => 1], $result);
        $this->assertSame(1, DB::table('pwa_subscriptions')->count());
    }

    private function createUser(int $roleId, string $name): array
    {
        $slug = strtolower(str_replace(' ', '', $name)) . '_' . bin2hex(random_bytes(3));
        $id = DB::table('users')->insertGetId([
            'role_id' => $roleId,
            'nama_depan' => $name,
            'nama_belakang' => 'Tester',
            'username' => $slug,
            'email' => $slug . '@example.test',
            'password' => Hash::make('secret123'),
            'alamat' => 'Test Address',
            'tanggal_lahir' => '1990-01-01',
            'foto' => 'default.png',
            'status' => 'aktif',
            'session_token' => 'token_' . $slug,
            'created_at' => now(),
            'last_login' => now(),
            'last_seen' => now(),
            'no_hp' => null,
            'is_active' => 1,
            'last_activity' => now(),
            'updated_at' => now(),
            'is_locked' => 0,
        ]);

        return (array) DB::table('users')->where('id', $id)->first();
    }

    private function bindSession(array $payload): void
    {
        session()->start();
        session()->flush();
        session()->put($payload);
    }

    private function bindRequest(string $method, string $uri, array $data = [], array $server = [], ?string $content = null): void
    {
        $request = Request::create(
            $uri,
            strtoupper($method),
            strtoupper($method) === 'GET' ? $data : $data,
            [],
            [],
            $server
            ,
            $content
        );
        $request->setLaravelSession(session()->driver());
        app()->instance('request', $request);
    }

    private function sessionPayload(array $user): array
    {
        return [
            'user_id' => (int) $user['id'],
            'role_id' => (int) $user['role_id'],
            'nama_depan' => $user['nama_depan'],
            'nama_belakang' => $user['nama_belakang'],
            'foto' => $user['foto'],
            'isLoggedIn' => true,
            'session_token' => $user['session_token'],
        ];
    }

    private function setUpSchema(): void
    {
        Schema::dropIfExists('absensi_detail');
        Schema::dropIfExists('absensi');
        Schema::dropIfExists('murid');
        Schema::dropIfExists('materi_ajar');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('guru_kegiatan');
        Schema::dropIfExists('kelas_history');
        Schema::dropIfExists('pwa_subscriptions');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('wa_recipients');
        Schema::dropIfExists('wa_templates');
        Schema::dropIfExists('lokasi_ibadah');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('role_id');
            $table->string('nama_depan', 100);
            $table->string('nama_belakang', 100)->default('');
            $table->string('username', 100)->unique();
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->text('alamat')->default('');
            $table->date('tanggal_lahir');
            $table->string('foto', 255)->default('default.png');
            $table->string('status', 20)->default('aktif');
            $table->string('session_token', 255)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->string('reset_token', 255)->nullable();
            $table->dateTime('reset_expires')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->dateTime('last_seen')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('last_activity')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->boolean('is_locked')->default(0);
        });

        Schema::create('kelas', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('tingkat_id')->nullable();
            $table->string('kode_kelas', 10)->unique();
            $table->string('nama_kelas', 50);
        });

        Schema::create('lokasi_ibadah', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nama_lokasi', 50)->unique();
        });

        Schema::create('murid', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('kelas_id');
            $table->string('jenis_kelamin', 1)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nama_depan', 100);
            $table->string('nama_belakang', 100)->default('');
            $table->string('panggilan', 50)->nullable();
            $table->string('gereja_asal', 150)->nullable();
            $table->string('unity', 50)->nullable();
            $table->text('alamat')->default('');
            $table->string('no_hp', 20)->default('');
            $table->string('foto', 255)->default('default.png');
            $table->string('status', 20)->default('aktif');
            $table->dateTime('created_at')->nullable();
            $table->string('kelas_sebelumnya', 10)->nullable();
        });

        Schema::create('absensi', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('tahun_ajaran_id')->nullable();
            $table->integer('guru_id');
            $table->integer('lokasi_id')->nullable();
            $table->string('lokasi_text', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('jenis_presensi', 20)->default('reguler');
            $table->date('tanggal');
            $table->time('jam');
            $table->boolean('is_dobel')->default(0);
            $table->boolean('is_resolved')->default(0);
            $table->string('selfie_foto', 255)->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('absensi_detail', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('absensi_id');
            $table->integer('murid_id');
            $table->string('status', 20);
            $table->date('tanggal');
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('materi_ajar', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('judul', 150);
            $table->text('catatan')->nullable();
            $table->string('kategori', 20);
            $table->integer('kelas_id');
            $table->string('file', 255)->default('');
            $table->string('link', 255)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->integer('total_view')->default(0);
        });

        Schema::create('audit_log', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('role', 30)->nullable();
            $table->string('action', 100);
            $table->string('severity', 20)->nullable();
            $table->integer('murid_id')->nullable();
            $table->integer('absensi_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->text('old_data')->nullable();
            $table->text('new_data')->nullable();
            $table->string('device', 50)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('guru_kegiatan', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('guru_id');
            $table->string('judul', 150)->nullable();
        });

        Schema::create('system_settings', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('setting_key', 100)->unique();
            $table->string('value', 255)->nullable();
            $table->string('setting_value', 255)->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('kelas_history', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('mode', 20);
            $table->string('tahun_ajaran', 20);
            $table->dateTime('executed_at')->nullable();
            $table->integer('executed_by')->nullable();
            $table->text('snapshot')->nullable();
            $table->boolean('is_locked')->default(0);
        });

        Schema::create('pwa_subscriptions', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('role_id');
            $table->text('endpoint');
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding', 30)->default('aes128gcm');
            $table->string('user_agent', 255)->nullable();
            $table->string('device_label', 120)->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('wa_templates', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('template_key', 120)->unique();
            $table->text('template_text');
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('wa_recipients', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('role_id');
            $table->string('no_hp', 25);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    private function seedReferenceData(): void
    {
        DB::table('kelas')->insert([
            'id' => 1,
            'tingkat_id' => null,
            'kode_kelas' => 'PG',
            'nama_kelas' => 'PG',
        ]);

        DB::table('lokasi_ibadah')->insert([
            'id' => 1,
            'nama_lokasi' => 'NICC',
        ]);
    }

    private function createMurid(int $kelasId = 1, string $namaDepan = 'Murid', string $namaBelakang = 'Test'): int
    {
        return DB::table('murid')->insertGetId([
            'kelas_id' => $kelasId,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2015-01-01',
            'nama_depan' => $namaDepan,
            'nama_belakang' => $namaBelakang,
            'panggilan' => $namaDepan,
            'alamat' => 'Test',
            'no_hp' => '620000000009',
            'foto' => 'default.png',
            'status' => 'aktif',
            'created_at' => now(),
        ]);
    }

    private function createAbsensi(int $guruId, int $lokasiId, string $tanggal, string $jam, string $jenis): int
    {
        return DB::table('absensi')->insertGetId([
            'guru_id' => $guruId,
            'lokasi_id' => $lokasiId,
            'lokasi_text' => 'NICC',
            'jenis_presensi' => $jenis,
            'tanggal' => $tanggal,
            'jam' => $jam,
            'created_at' => now(),
        ]);
    }

    private function createAbsensiDetail(int $absensiId, int $muridId, string $status, string $tanggal): int
    {
        return DB::table('absensi_detail')->insertGetId([
            'absensi_id' => $absensiId,
            'murid_id' => $muridId,
            'status' => $status,
            'tanggal' => $tanggal,
            'created_at' => now(),
        ]);
    }

    private function resetCompatConnection(): void
    {
        $reflection = new \ReflectionClass(\Config\Database::class);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
