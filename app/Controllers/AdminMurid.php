<?php 

namespace App\Controllers;

use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminMurid extends BaseController
{
    private const UNITY_OPTIONS = ['Unity Peter', 'Unity David', 'Unity Samuel', 'Unity Joshua'];
    private ?bool $hasGerejaAsalColumn = null;
    private ?bool $hasUnityColumn = null;

    protected $db;

    public function __construct(){
        parent::__construct();
        $this->db = \Config\Database::connect();
        helper(['session', 'wa']);
    }

    /* =========================
     * LIST MURID
     * ========================= */
    public function index()
    {
        $kelasId = (int) ($this->request->getGet('kelas_id') ?? 0);
        $q       = trim((string) ($this->request->getGet('q') ?? ''));

        $builder = $this->db->table('murid m')
            ->select('m.*, k.nama_kelas')
            ->join('kelas k', 'k.id = m.kelas_id', 'left')
            ->orderBy('k.nama_kelas', 'ASC')
            ->orderBy('m.nama_depan', 'ASC');

        if ($kelasId > 0) {
            $builder->where('m.kelas_id', $kelasId);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('m.nama_depan', $q)
                ->orLike('m.nama_belakang', $q)
                ->orLike('m.panggilan', $q)
                ->groupEnd();
        }

        return view('admin/murid_index', [
            'murid'      => $builder->get()->getResultArray(),
            'kelas'      => $this->db->table('kelas')->orderBy('nama_kelas', 'ASC')->get()->getResultArray(),
            'kelasAktif' => $kelasId,
            'q'          => $q,
        ]);
    }

    public function edit($id)
    {
        $murid = $this->db->table('murid')->where('id', (int) $id)->get()->getRowArray();
        if (!$murid) {
            return redirect()->back()->with('error', 'Data murid tidak ditemukan.');
        }

        return view('admin/murid_edit', [
            'murid' => $murid,
            'kelas' => $this->db->table('kelas')->orderBy('nama_kelas', 'ASC')->get()->getResultArray(),
            'unityOptions' => self::UNITY_OPTIONS,
        ]);
    }

    public function update($id)
    {
        $murid = $this->db->table('murid')->where('id', (int) $id)->get()->getRowArray();
        if (!$murid) {
            return redirect()->back()->with('error', 'Data murid tidak ditemukan.');
        }

        $kelasId = (int) ($this->request->getPost('kelas_id') ?? 0);
        $kelasExists = $kelasId > 0
            && $this->db->table('kelas')->where('id', $kelasId)->countAllResults() > 0;
        if (!$kelasExists) {
            return redirect()->back()->withInput()->with('error', 'Kelas tidak valid.');
        }

        $rules = [
            'nama_depan'    => 'required',
            'tanggal_lahir' => 'required',
            'jenis_kelamin' => 'required|in_list[L,P]',
            'kelas_id'      => 'required',
            'unity'         => 'permit_empty|in_list[Unity Peter,Unity David,Unity Samuel,Unity Joshua]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Data wajib belum lengkap atau tidak valid.');
        }

        $noHpRaw = $this->request->getPost('no_hp');
        $noHp = function_exists('normalizeWa')
            ? normalizeWa($noHpRaw)
            : (function_exists('formatWA') ? formatWA($noHpRaw) : preg_replace('/[^0-9]/', '', (string) $noHpRaw));

        $data = [
            'nama_depan'    => trim((string) $this->request->getPost('nama_depan')),
            'nama_belakang' => trim((string) $this->request->getPost('nama_belakang')),
            'panggilan'     => trim((string) $this->request->getPost('panggilan')),
            'kelas_id'      => $kelasId,
            'jenis_kelamin' => (string) $this->request->getPost('jenis_kelamin'),
            'tanggal_lahir' => (string) $this->request->getPost('tanggal_lahir'),
            'alamat'        => trim((string) $this->request->getPost('alamat')),
            'no_hp'         => $noHp,
        ];

        if ($this->hasMuridColumn('gereja_asal')) {
            $data['gereja_asal'] = trim((string) $this->request->getPost('gereja_asal'));
        }
        if ($this->hasMuridColumn('unity')) {
            $data['unity'] = $this->sanitizeUnity($this->request->getPost('unity'));
        }

        $hasChanges = $this->hasDataChanges($murid, $data);

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $uploadDir = FCPATH . 'uploads/murid';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $namaFoto = $foto->getRandomName();
            $foto->move($uploadDir, $namaFoto);
            $data['foto'] = $namaFoto;
            $hasChanges = true;
        }

        if (!$hasChanges) {
            return redirect()->back()->with('warning', 'Tidak ada perubahan data.');
        }

        $updated = $this->db->table('murid')->where('id', (int) $id)->update($data);
        if ($updated !== true) {
            $dbError = $this->db->error();
            $errMsg = !empty($dbError['message']) ? $dbError['message'] : 'Update gagal di database.';
            log_message('error', 'Gagal update murid id {id}: {msg}', ['id' => $id, 'msg' => $errMsg]);
            return redirect()->back()->withInput()->with('error', 'Gagal simpan perubahan: '.$errMsg);
        }

        return redirect()->to($this->resolveMuridListUrl())
            ->with('success', 'Data murid berhasil diperbarui.');
    }

    /* =========================
     * FORM UPLOAD
     * ========================= */
    public function importForm()
    {
        return view('admin/murid_import');
    }

    /* =========================
     * PREVIEW EXCEL
     * ========================= */
    public function importPreview()
    {
        $file = $this->request->getFile('file_excel');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        unset($rows[0]); // hapus header

        $data = [];

        foreach ($rows as $row) {

            /**
             * ASUMSI KOLOM EXCEL:
             * [1] Nama Lengkap
             * [3] ID Kelas
             * [6] No HP Ortu
             * [7] Alamat
             * [8] Jenis Kelamin
             * [9] Nama Panggilan
             */

            $namaLengkap = trim($row[1] ?? '');
            $kelas       = (int)($row[3] ?? 0);

            if ($namaLengkap === '' || $kelas === 0) {
                continue;
            }

            $namaArr = explode(' ', $namaLengkap, 2);

            $data[] = [
                'nama_depan'    => $namaArr[0],
                'nama_belakang' => $namaArr[1] ?? '',
                'panggilan'     => trim($row[9] ?? $namaArr[0]), // fallback ke nama depan
                'kelas_id'      => $kelas,
                'jenis_kelamin' => strtoupper(trim($row[8] ?? '')),
                'alamat'        => trim($row[7] ?? ''),
                'no_hp'         => formatWA($row[6] ?? ''),
                'foto'          => 'default.png'
            ];
        }

        session()->set('import_murid', $data);

        return view('admin/murid_import_preview', [
            'data' => $data
        ]);
    }

    /* =========================
     * EXECUTE IMPORT
     * ========================= */
    public function importExecute()
    {
        $data = session()->get('import_murid');

        if (!$data) {
            return redirect()->to('/admin/murid/import')
                ->with('error', 'Data preview tidak ditemukan');
        }

        $inserted = 0;

        foreach ($data as $m) {

            // CEGAH DUPLIKAT (NAMA + KELAS)
            $cek = $this->db->table('murid')
                ->where('nama_depan', $m['nama_depan'])
                ->where('nama_belakang', $m['nama_belakang'])
                ->where('kelas_id', $m['kelas_id'])
                ->get()
                ->getRow();

            if ($cek) {
                continue;
            }

            $this->db->table('murid')->insert([
                'nama_depan'    => $m['nama_depan'],
                'nama_belakang' => $m['nama_belakang'],
                'panggilan'     => $m['panggilan'],
                'kelas_id'      => $m['kelas_id'],
                'jenis_kelamin' => $m['jenis_kelamin'],
                'alamat'        => $m['alamat'],
                'no_hp'         => $m['no_hp'],
                'foto'          => $m['foto'],
                'status'        => 'aktif',
                'created_at'    => date('Y-m-d H:i:s')
            ]);

            $inserted++;
        }

        session()->remove('import_murid');

        return redirect()->to('/admin/murid/import')
            ->with('success', "Import berhasil. Murid ditambahkan: {$inserted}");
    }

    private function sanitizeUnity($unity): ?string
    {
        $value = trim((string) $unity);
        if ($value === '') {
            return null;
        }

        return in_array($value, self::UNITY_OPTIONS, true) ? $value : null;
    }

    private function hasMuridColumn(string $column): bool
    {
        if ($column === 'gereja_asal' && $this->hasGerejaAsalColumn !== null) {
            return $this->hasGerejaAsalColumn;
        }
        if ($column === 'unity' && $this->hasUnityColumn !== null) {
            return $this->hasUnityColumn;
        }

        $exists = $this->hasTableColumn('murid', $column);

        if ($column === 'gereja_asal') {
            $this->hasGerejaAsalColumn = $exists;
        } elseif ($column === 'unity') {
            $this->hasUnityColumn = $exists;
        }

        return $exists;
    }

    private function resolveMuridListUrl(): string
    {
        $uri = trim((string) uri_string(), '/');
        if (str_starts_with($uri, 'dashboard/superadmin/')) {
            return '/dashboard/superadmin/murid';
        }

        return '/admin/murid';
    }

    private function hasDataChanges(array $old, array $new): bool
    {
        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;
            if ((string) $oldValue !== (string) $value) {
                return true;
            }
        }

        return false;
    }
}
