<?php
$logoPath = FCPATH . 'assets/logo/dscmkids.png';
?>

<table width="100%" style="border-bottom:2px solid #000; margin-bottom:10px;">
  <tr>
    <td width="15%" align="center">
      <?php if (file_exists($logoPath)): ?>
        <img src="<?= $logoPath ?>" style="width:70px;">
      <?php endif; ?>
    </td>

    <td width="85%" align="center">
      <div style="font-size:16px; font-weight:bold;">
        PRESENSI KEHADIRAN MURID
      </div>
      <div style="font-size:14px; font-weight:bold;">
        DSCM KIDS
      </div>
      <div style="font-size:11px; margin-top:4px;">
        Periode: <?= esc($start ?? '-') ?> s/d <?= esc($end ?? '-') ?>
      </div>
    </td>
  </tr>
</table>
