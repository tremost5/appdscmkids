<?php

if (!function_exists('kelasMap')) {
    function kelasMap()
    {
        return [
            1 => ['label' => 'PG',  'color' => 'secondary'],
            2 => ['label' => 'TKA', 'color' => 'info'],
            3 => ['label' => 'TKB', 'color' => 'primary'],
            4 => ['label' => '1',   'color' => 'success'],
            5 => ['label' => '2',   'color' => 'success'],
            6 => ['label' => '3',   'color' => 'success'],
            7 => ['label' => '4',   'color' => 'warning'],
            8 => ['label' => '5',   'color' => 'warning'],
            9 => ['label' => '6',   'color' => 'danger'],
            11 => ['label' => 'TR', 'color' => 'dark'],
        ];
    }
}

if (!function_exists('kelasGroupMap')) {
    function kelasGroupMap()
    {
        return [
            'pg' => ['label' => 'PG', 'codes' => ['PG']],
            'tk' => ['label' => 'TK', 'codes' => ['TKA', 'TKB']],
            '1_2' => ['label' => '1 & 2', 'codes' => ['1', '2']],
            '3_4' => ['label' => '3 & 4', 'codes' => ['3', '4']],
            '5' => ['label' => '5', 'codes' => ['5']],
            '6' => ['label' => '6', 'codes' => ['6']],
            'tr' => ['label' => 'TR', 'codes' => ['TR']],
        ];
    }
}

if (!function_exists('kelasLabel')) {
    function kelasLabel($kelasId)
    {
        return kelasMap()[$kelasId]['label'] ?? '-';
    }
}

if (!function_exists('kelasBadge')) {
    function kelasBadge($kelasId)
    {
        $map = kelasMap();
        if (!isset($map[$kelasId])) {
            return '<span class="badge badge-secondary">-</span>';
        }

        return '<span class="badge badge-' . $map[$kelasId]['color'] . '">' .
            $map[$kelasId]['label'] .
        '</span>';
    }
}

if (!function_exists('unityMetaMap')) {
    function unityMetaMap()
    {
        return [
            'Unity Peter' => ['color' => '#facc15', 'text' => '#854d0e', 'short' => 'P'],
            'Unity David' => ['color' => '#ef4444', 'text' => '#ffffff', 'short' => 'D'],
            'Unity Samuel' => ['color' => '#3b82f6', 'text' => '#ffffff', 'short' => 'S'],
            'Unity Joshua' => ['color' => '#22c55e', 'text' => '#ffffff', 'short' => 'J'],
        ];
    }
}

if (!function_exists('unityBadge')) {
    function unityBadge($unity)
    {
        $unity = trim((string) $unity);
        if ($unity === '') {
            return '';
        }

        $meta = unityMetaMap()[$unity] ?? null;
        if (!$meta) {
            return '';
        }

        $title = htmlspecialchars($unity, ENT_QUOTES, 'UTF-8');
        $bg = htmlspecialchars($meta['color'], ENT_QUOTES, 'UTF-8');
        $fg = htmlspecialchars($meta['text'], ENT_QUOTES, 'UTF-8');

        return '<span title="'.$title.'" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:'.$bg.';color:'.$fg.';font-size:12px;font-weight:700;line-height:1">★</span>';
    }
}
