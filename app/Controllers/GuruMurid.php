<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MuridModel;

class GuruMurid extends BaseController
{
    private const UNITY_OPTIONS = ['Unity Peter', 'Unity David', 'Unity Samuel', 'Unity Joshua'];
    private ?bool $hasGerejaAsalColumn = null;
    private ?bool $hasUnityColumn = null;

    protected $muridModel;
    protected $db;

    public function __construct(){
        parent::__construct();
        $this->muridModel = new MuridModel();
        $this->db = \Config\Database::connect();

        // helper audit sudah autoload (OPS I A)
        helper(['audit']);
    }

    /* =========================
     * LIST MURID
     * ========================= */
    public function index()
    {
        $kelasId = $this->request->getGet('kelas_id');
        $search  = $this->request->getGet('q');

        $builder = $this->db->table('murid m')
            ->select('m.*, k.nama_kelas')
            ->join('kelas k', 'k.id = m.kelas_id', 'left');

        if ($search) {
            $builder->groupStart()
                ->like('m.nama_depan', $search)
                ->orLike('m.nama_belakang', $search)
                ->orLike('m.panggilan', $search)
                ->orLike('m.no_hp', $search)
                ->groupEnd();
        }

        if ($kelasId) {
            $builder->where('m.kelas_id', $kelasId);
        }

        $builder
            ->orderBy('k.nama_kelas', 'ASC')
            ->orderBy('m.nama_depan', 'ASC')
            ->orderBy('m.nama_belakang', 'ASC');

        return view('guru/murid/index', [
            'murid'      => $builder->get()->getResultArray(),
            'kelas'      => $this->db->table('kelas')->orderBy('nama_kelas','ASC')->get()->getResultArray(),
            'kelasAktif' => $kelasId,
            'q'          => $search
        ]);
    }

    /* =========================
     * FORM CREATE
     * ========================= */
    public function create()
    {
        return view('guru/murid/create', [
            'kelas' => $this->db->table('kelas')->orderBy('nama_kelas','ASC')->get()->getResultArray(),
            'unityOptions' => self::UNITY_OPTIONS,
        ]);
    }

    /* =========================
     * SIMPAN MURID
     * ========================= */
    public function store()
    {
        if (!$this->validateOptionalMuridColumns()) {
            return redirect()->back()->withInput()->with('error', $this->optionalColumnErrorMessage());
        }

        $rules = [
            'nama_depan'    => 'required',
            'tanggal_lahir' => 'required',
            'jenis_kelamin' => 'required',
            'kelas_id'      => 'required',
            'unity'         => 'permit_empty|in_list[Unity Peter,Unity David,Unity Samuel,Unity Joshua]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Data wajib belum lengkap');
        }

        $noHpRaw = $this->request->getPost('no_hp');
        $noHp = function_exists('normalizeWa')
            ? normalizeWa($noHpRaw)
            : (function($v){
                $v = preg_replace('/[^0-9]/','',(string)$v);
                return substr($v,0,1)==='0' ? '62'.substr($v,1) : $v;
            })($noHpRaw);

        $unity = $this->sanitizeUnity($this->request->getPost('unity'));

        $data = [
            'nama_depan'    => $this->request->getPost('nama_depan'),
            'nama_belakang' => $this->request->getPost('nama_belakang'),
            'panggilan'     => $this->request->getPost('panggilan'),
            'kelas_id'      => $this->request->getPost('kelas_id'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir'),
            'alamat'        => $this->request->getPost('alamat'),
            'no_hp'         => $noHp,
            'status'        => 'aktif'
        ];
        if ($this->hasMuridColumn('gereja_asal')) {
            $data['gereja_asal'] = $this->request->getPost('gereja_asal');
        }
        if ($this->hasMuridColumn('unity')) {
            $data['unity'] = $unity;
        }

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $namaFoto = $foto->getRandomName();
            $foto->move(FCPATH.'uploads/murid', $namaFoto);
            $data['foto'] = $namaFoto;
        }

        $this->muridModel->insert($data);
        $muridId = $this->muridModel->getInsertID();

        // ✅ AUDIT
        logAudit(
            'create_murid',
            'info',
            [
                'murid_id' => $muridId,
                'new'      => $data
            ]
        );

        return redirect()->to('guru/murid')->with('success','Murid berhasil ditambahkan');
    }

    /* =========================
     * FORM EDIT
     * ========================= */
    public function edit($id)
    {
        $murid = $this->muridModel->find($id);
        if (!$murid) {
            return redirect()->to('guru/murid')->with('error', 'Data murid tidak ditemukan');
        }

        return view('guru/murid/edit', [
            'murid' => $murid,
            'kelas' => $this->db->table('kelas')->orderBy('nama_kelas','ASC')->get()->getResultArray(),
            'unityOptions' => self::UNITY_OPTIONS,
        ]);
    }

    /* =========================
     * UPDATE MURID
     * ========================= */
    public function update($id)
    {
        $old = $this->muridModel->find($id);
        if (!$old) {
            return redirect()->to('guru/murid')->with('error', 'Data murid tidak ditemukan');
        }

        if (!$this->validateOptionalMuridColumns()) {
            return redirect()->back()->withInput()->with('error', $this->optionalColumnErrorMessage());
        }

        $kelasId = (int) ($this->request->getPost('kelas_id') ?? 0);
        $kelasExists = $kelasId > 0
            && $this->db->table('kelas')->where('id', $kelasId)->countAllResults() > 0;
        if (!$kelasExists) {
            return redirect()->back()->withInput()->with('error', 'Kelas tidak valid');
        }

        $noHpRaw = $this->request->getPost('no_hp');
        $noHp = function_exists('normalizeWa')
            ? normalizeWa($noHpRaw)
            : (function($v){
                $v = preg_replace('/[^0-9]/','',(string)$v);
                return substr($v,0,1)==='0' ? '62'.substr($v,1) : $v;
            })($noHpRaw);

        $unity = $this->sanitizeUnity($this->request->getPost('unity'));

        $data = [
            'nama_depan'    => $this->request->getPost('nama_depan'),
            'nama_belakang' => $this->request->getPost('nama_belakang'),
            'panggilan'     => $this->request->getPost('panggilan'),
            'kelas_id'      => $kelasId,
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir'),
            'alamat'        => $this->request->getPost('alamat'),
            'no_hp'         => $noHp,
        ];
        if ($this->hasMuridColumn('gereja_asal')) {
            $data['gereja_asal'] = $this->request->getPost('gereja_asal');
        }
        if ($this->hasMuridColumn('unity')) {
            $data['unity'] = $unity;
        }

        $hasChanges = $this->hasDataChanges($old, $data);

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $namaFoto = $foto->getRandomName();
            $foto->move(FCPATH.'uploads/murid', $namaFoto);
            $data['foto'] = $namaFoto;
            $hasChanges = true;
        }

        if (!$hasChanges) {
            return redirect()->to('guru/murid?highlight='.$id)
                ->with('warning', 'Tidak ada perubahan data');
        }

        $updated = $this->muridModel->update($id, $data);
        if ($updated !== true) {
            $dbError = $this->db->error();
            $errMsg = !empty($dbError['message']) ? $dbError['message'] : 'Update gagal di database.';
            log_message('error', 'Gagal update murid id {id}: {msg}', ['id' => $id, 'msg' => $errMsg]);
            return redirect()->back()->withInput()->with('error', 'Gagal simpan perubahan: '.$errMsg);
        }

        // ✅ AUDIT
        logAudit(
            'update_murid',
            'warning',
            [
                'murid_id' => $id,
                'old'      => $old,
                'new'      => $data
            ]
        );

        return redirect()->to('guru/murid?highlight='.$id)
            ->with('success','Data murid berhasil diperbarui');
    }

    /* =========================
     * NONAKTIFKAN MURID
     * ========================= */
    public function nonaktif($id)
    {
        $old = $this->muridModel->find($id);

        $this->muridModel->update($id, ['status'=>'nonaktif']);

        // ✅ AUDIT
        logAudit(
            'nonaktif_murid',
            'danger',
            [
                'murid_id' => $id,
                'old'      => $old,
                'new'      => ['status'=>'nonaktif']
            ]
        );

        return redirect()->to('guru/murid')->with('success','Murid dinonaktifkan');
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

    private function validateOptionalMuridColumns(): bool
    {
        $postedGereja = trim((string) $this->request->getPost('gereja_asal'));
        $postedUnity = trim((string) $this->request->getPost('unity'));

        if ($postedGereja !== '' && !$this->hasMuridColumn('gereja_asal')) {
            return false;
        }
        if ($postedUnity !== '' && !$this->hasMuridColumn('unity')) {
            return false;
        }

        return true;
    }

    private function optionalColumnErrorMessage(): string
    {
        $missing = [];
        if (trim((string) $this->request->getPost('gereja_asal')) !== '' && !$this->hasMuridColumn('gereja_asal')) {
            $missing[] = 'gereja_asal';
        }
        if (trim((string) $this->request->getPost('unity')) !== '' && !$this->hasMuridColumn('unity')) {
            $missing[] = 'unity';
        }

        if (empty($missing)) {
            return 'Struktur database belum sinkron.';
        }

        return 'Kolom database belum tersedia: '.implode(', ', $missing).'. Jalankan SQL ALTER TABLE di hosting.';
    }
}
