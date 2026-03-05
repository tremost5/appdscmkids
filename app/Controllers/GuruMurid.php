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
        return view('guru/murid/edit', [
            'murid' => $this->muridModel->find($id),
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

        $this->muridModel->update($id, $data);

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

        $result = $this->db->query("SHOW COLUMNS FROM murid LIKE ?", [$column])->getRowArray();
        $exists = !empty($result);

        if ($column === 'gereja_asal') {
            $this->hasGerejaAsalColumn = $exists;
        } elseif ($column === 'unity') {
            $this->hasUnityColumn = $exists;
        }

        return $exists;
    }
}
