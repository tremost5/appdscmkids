<?php

namespace App\Controllers;

class AdminMateri extends BaseController
{
    protected $db;
    protected $perPage = 8;
    private const ALLOWED_KATEGORI = ['pdf', 'video', 'link'];

    public function __construct(){
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /* =============================
     * MAIN PAGE
     * ============================= */
    public function index()
    {
        return view('admin/bahan_ajar');
    }

    // =============================
// FETCH (AJAX LIST + SEARCH + PAGINATION)
// =============================
public function fetch()
{
    if (!$this->request->isAJAX()) {
        return $this->response->setStatusCode(403);
    }

    $page = (int) ($this->request->getGet('page') ?? 1);
    $q    = trim($this->request->getGet('q') ?? '');

    $limit  = 10;
    $offset = ($page - 1) * $limit;

    $builder = $this->db->table('materi_ajar m')
        ->select('m.*, k.nama_kelas')
        ->join('kelas k', 'k.id = m.kelas_id', 'left');

    if ($q !== '') {
        $builder->groupStart()
            ->like('m.judul', $q)
            ->orLike('m.catatan', $q)
        ->groupEnd();
    }

    $total = $builder->countAllResults(false);

    $data = $builder
        ->orderBy('m.created_at', 'DESC')
        ->limit($limit, $offset)
        ->get()
        ->getResultArray();

    return $this->response->setJSON([
        'data' => $data,
        'page' => $page,
        'last' => max(1, (int) ceil($total / $limit)),
    ]);
}

    /* =============================
     * UPLOAD (AJAX)
     * ============================= */
    public function upload()
    {
        if (!$this->request->isAJAX()) return $this->response->setStatusCode(403);

        $payload = $this->validateMateriPayload();
        if ($payload['error']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $payload['message'],
            ])->setStatusCode(422);
        }

        $namaFile = $this->storeMateriFile($payload['file'], $payload['kategori']);
        if ($payload['file'] && $payload['file']->getError() !== UPLOAD_ERR_NO_FILE && $namaFile === null) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'File upload gagal disimpan',
            ])->setStatusCode(422);
        }

        $this->db->table('materi_ajar')->insert([
            'judul'      => $payload['judul'],
            'catatan'    => $payload['catatan'],
            'kelas_id'   => $payload['kelas_id'],
            'kategori'   => $payload['kategori'],
            'file'       => $namaFile ?? '',
            'link'       => $payload['link'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['status'=>'ok']);
    }

    /* =============================
     * UPDATE
     * ============================= */
    public function updateAjax($id)
    {
        if (!$this->request->isAJAX()) return $this->response->setStatusCode(403);

        $materi = $this->db->table('materi_ajar')->where('id', (int) $id)->get()->getRowArray();
        if (!$materi) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan',
            ])->setStatusCode(404);
        }

        $payload = $this->validateMateriPayload(true);
        if ($payload['error']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $payload['message'],
            ])->setStatusCode(422);
        }

        $update = [
            'judul'    => $payload['judul'],
            'catatan'  => $payload['catatan'],
            'kelas_id' => $payload['kelas_id'],
            'kategori' => $payload['kategori'],
            'link'     => $payload['link'],
        ];

        $file = $payload['file'];
        if ($file && $file->isValid()) {
            $namaFile = $this->storeMateriFile($file, $payload['kategori']);
            if ($namaFile === null) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'File upload gagal disimpan',
                ])->setStatusCode(422);
            }
            $update['file'] = $namaFile;
            $this->removeMateriFile($materi['file'] ?? null);
        } elseif ($payload['kategori'] === 'link') {
            $update['file'] = '';
            $this->removeMateriFile($materi['file'] ?? null);
        }

        $this->db->table('materi_ajar')->where('id',$id)->update($update);
        return $this->response->setJSON(['status'=>'ok']);
    }

    /* =============================
     * DELETE
     * ============================= */
    public function deleteAjax($id)
    {
        if (!$this->request->isAJAX()) return $this->response->setStatusCode(403);

        $materi = $this->db->table('materi_ajar')->where('id', (int) $id)->get()->getRowArray();
        if (!$materi) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan',
            ])->setStatusCode(404);
        }

        $this->removeMateriFile($materi['file'] ?? null);
        $this->db->table('materi_ajar')->where('id',$id)->delete();
        return $this->response->setJSON(['status'=>'ok']);
    }

    private function validateMateriPayload(bool $isUpdate = false): array
    {
        $judul = trim((string) ($this->request->getPost('judul') ?? ''));
        $catatan = trim((string) ($this->request->getPost('catatan') ?? ''));
        $kelasId = (int) ($this->request->getPost('kelas_id') ?? 0);
        $kategori = strtolower(trim((string) ($this->request->getPost('kategori') ?? '')));
        $link = trim((string) ($this->request->getPost('link') ?? ''));
        $file = $this->request->getFile('file');

        if ($judul === '') {
            return ['error' => true, 'message' => 'Judul wajib diisi'];
        }
        if ($kelasId <= 0 || !$this->db->table('kelas')->where('id', $kelasId)->countAllResults()) {
            return ['error' => true, 'message' => 'Kelas tidak valid'];
        }
        if (!in_array($kategori, self::ALLOWED_KATEGORI, true)) {
            return ['error' => true, 'message' => 'Kategori tidak valid'];
        }

        if ($kategori === 'link') {
            if ($link === '' || filter_var($link, FILTER_VALIDATE_URL) === false) {
                return ['error' => true, 'message' => 'Link wajib valid untuk kategori link'];
            }
        } else {
            $requiresFile = !$isUpdate || ($file && $file->getError() !== UPLOAD_ERR_NO_FILE);
            if ($requiresFile && (!$file || !$file->isValid())) {
                return ['error' => true, 'message' => 'File wajib diunggah dan valid'];
            }
            if ($file && $file->isValid() && !$this->isAllowedMateriFile($file, $kategori)) {
                return ['error' => true, 'message' => 'Tipe file tidak sesuai kategori'];
            }
            $link = '';
        }

        if ($file && !$file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            return ['error' => true, 'message' => 'File upload tidak valid'];
        }

        return [
            'error' => false,
            'judul' => $judul,
            'catatan' => $catatan,
            'kelas_id' => $kelasId,
            'kategori' => $kategori,
            'link' => $link,
            'file' => $file,
        ];
    }

    private function isAllowedMateriFile($file, string $kategori): bool
    {
        $ext = strtolower((string) $file->getExtension());
        $mime = strtolower((string) $file->getMimeType());

        if ($kategori === 'pdf') {
            return $ext === 'pdf' || str_contains($mime, 'pdf');
        }

        if ($kategori === 'video') {
            return in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'], true)
                || str_starts_with($mime, 'video/');
        }

        return false;
    }

    private function storeMateriFile($file, string $kategori): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $path = FCPATH.'uploads/materi';
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        $prefix = $kategori.'_'.time().'_';
        $namaFile = $prefix.$file->getRandomName();
        $file->move($path, $namaFile);

        return $namaFile;
    }

    private function removeMateriFile(?string $filename): void
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return;
        }

        $path = FCPATH.'uploads/materi/'.basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
