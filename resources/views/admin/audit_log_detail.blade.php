@extends('layouts/adminlte')
@section('content')

<h4>Detail Audit</h4>

<?php
$namaUser = trim((($row['nama_depan'] ?? '') . ' ' . ($row['nama_belakang'] ?? '')));

$fieldLabel = static function (string $field): string {
    $map = [
        'id' => 'ID',
        'murid_id' => 'Murid',
        'absensi_id' => 'Presensi',
        'status' => 'Status',
        'tanggal' => 'Tanggal',
        'created_at' => 'Waktu',
        'updated_at' => 'Update',
    ];

    return $map[$field] ?? ucwords(str_replace('_', ' ', $field));
};

$isListArray = static function (array $arr): bool {
    return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
};

$formatValue = static function ($value): string {
    if (is_array($value)) {
        return '<pre class="mb-0">' . esc(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if ($value === null || $value === '') {
        return '<span class="text-muted">-</span>';
    }

    return esc((string) $value);
};

$renderAuditTable = static function ($json) use ($fieldLabel, $isListArray, $formatValue) {
    $decoded = json_decode((string) $json, true);
    if (! is_array($decoded) || empty($decoded)) {
        return '<div class="text-muted">Tidak ada data</div>';
    }

    // Jika old/new berupa list record, tampilkan sebagai tabel per baris.
    if ($isListArray($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
        $columns = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach (array_keys($item) as $col) {
                if (! in_array($col, $columns, true)) {
                    $columns[] = $col;
                }
            }
        }

        $html  = '<div class="table-responsive">';
        $html .= '<table class="table table-sm table-bordered mb-0">';
        $html .= '<thead class="table-light"><tr>';
        foreach ($columns as $col) {
            $html .= '<th>' . esc($fieldLabel((string) $col)) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($decoded as $item) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $value = is_array($item) ? ($item[$col] ?? null) : null;
                $html .= '<td>' . $formatValue($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }

    // Jika object biasa, tampilkan key-value.
    $html  = '<div class="table-responsive">';
    $html .= '<table class="table table-sm table-bordered mb-0">';
    $html .= '<thead class="table-light"><tr><th style="width:30%">Field</th><th>Nilai</th></tr></thead><tbody>';
    foreach ($decoded as $key => $value) {
        $html .= '<tr><td><strong>' . esc($fieldLabel((string) $key)) . '</strong></td><td>' . $formatValue($value) . '</td></tr>';
    }
    $html .= '</tbody></table></div>';

    return $html;
};
?>

<div class="alert alert-info">
  <b><?= esc(strtoupper(str_replace('_', ' ', $row['action'] ?? 'aksi'))) ?></b><br>
  User: <strong><?= esc($namaUser ?: '-') ?></strong> (<?= esc($row['role'] ?? '-') ?>)<br>
  IP: <?= esc($row['ip_address'] ?? '-') ?><br>
  Waktu: <?= esc($row['created_at'] ?? '-') ?>
</div>

<?php if (! empty($row['old_data'])): ?>
  <h6>Data Lama</h6>
  <?= $renderAuditTable($row['old_data']) ?>
<?php endif ?>

<?php if (! empty($row['new_data'])): ?>
  <h6 class="mt-3">Data Baru</h6>
  <?= $renderAuditTable($row['new_data']) ?>
<?php endif ?>

<a href="<?= base_url('admin/audit-log') ?>" class="btn btn-secondary mt-3">
  Kembali
</a>

@endsection
