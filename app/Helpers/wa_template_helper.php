<?php

use Config\Database;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!function_exists('waTemplateDefaults')) {
    function waTemplateDefaults(): array
    {
        return [
            'register_admin' => "PENDAFTARAN GURU BARU\n\nNama: {nama_lengkap}\nUsername: {username}\nNo WA: {no_hp}\nStatus: {status}",
            'register_user' => "Shalom {nama_lengkap}\n\nTerima kasih telah mendaftar sebagai guru.\nStatus akun Anda saat ini: {status}\n\nKami akan menghubungi Anda kembali setelah akun diaktifkan.",
            'guru_status_active' => "Shalom {nama_lengkap}\n\nAkun guru Anda telah AKTIF.\nUsername: {username}\nSilakan login dan mulai menggunakan sistem.",
            'guru_status_inactive' => "Shalom {nama_lengkap}\n\nAkun guru Anda saat ini DINONAKTIFKAN.\nJika ada kesalahan, silakan hubungi Admin Sekolah.",
        ];
    }
}

if (!function_exists('waTemplateEnsureSchema')) {
    function waTemplateEnsureSchema(): void
    {
        $db = Database::connect();
        if ($db->tableExists('wa_templates')) {
            return;
        }

        Schema::create('wa_templates', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('template_key', 120)->unique();
            $table->text('template_text');
            $table->dateTime('updated_at')->nullable();
        });
    }
}

if (!function_exists('waRecipientEnsureSchema')) {
    function waRecipientEnsureSchema(): void
    {
        $db = Database::connect();
        if ($db->tableExists('wa_recipients')) {
            return;
        }

        Schema::create('wa_recipients', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('role_id');
            $table->string('no_hp', 25);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('user_id', 'uniq_wa_recipient_user');
        });
    }
}

if (!function_exists('waTemplateGet')) {
    function waTemplateGet(string $key, string $default = ''): string
    {
        waTemplateEnsureSchema();
        $db = Database::connect();
        $row = $db->table('wa_templates')
            ->where('template_key', $key)
            ->get()
            ->getRowArray();

        if ($row && isset($row['template_text'])) {
            return (string) $row['template_text'];
        }

        if ($default !== '') {
            return $default;
        }

        $defaults = waTemplateDefaults();
        return (string) ($defaults[$key] ?? '');
    }
}

if (!function_exists('waTemplateUpsert')) {
    function waTemplateUpsert(string $key, string $text): void
    {
        waTemplateEnsureSchema();
        $db = Database::connect();
        $row = $db->table('wa_templates')
            ->where('template_key', $key)
            ->get()
            ->getRowArray();

        if ($row) {
            $db->table('wa_templates')
                ->where('template_key', $key)
                ->update(['template_text' => $text]);
            return;
        }

        $db->table('wa_templates')->insert([
            'template_key' => $key,
            'template_text' => $text,
        ]);
    }
}

if (!function_exists('waTemplateRender')) {
    function waTemplateRender(string $template, array $vars = []): string
    {
        $result = $template;
        foreach ($vars as $key => $value) {
            $result = str_replace('{' . $key . '}', (string) $value, $result);
        }
        return $result;
    }
}

if (!function_exists('waRecipients')) {
    function waRecipients(array $roleIds = [1, 2], bool $onlyActive = true): array
    {
        waRecipientEnsureSchema();
        $db = Database::connect();
        $builder = $db->table('wa_recipients wr')
            ->select('wr.user_id, wr.no_hp, wr.role_id, wr.is_active')
            ->join('users u', 'u.id = wr.user_id', 'inner')
            ->whereIn('wr.role_id', $roleIds);

        if ($onlyActive) {
            $builder->where('wr.is_active', 1);
        }

        return $builder->get()->getResultArray();
    }
}

if (!function_exists('waRecipientNumbers')) {
    function waRecipientNumbers(array $roleIds = [1, 2], bool $onlyActive = true): array
    {
        $rows = waRecipients($roleIds, $onlyActive);
        $numbers = [];
        foreach ($rows as $row) {
            $no = trim((string) ($row['no_hp'] ?? ''));
            if ($no !== '') {
                $numbers[$no] = true;
            }
        }
        return array_keys($numbers);
    }
}
