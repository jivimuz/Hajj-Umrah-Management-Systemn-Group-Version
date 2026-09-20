<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $parent = DB::table('m_modules')->where('code', 'JMA')->first();

        if ($parent && !DB::table('m_modules')->where('code', 'JMD')->exists()) {
            DB::table('m_modules')->insert([
                'name' => 'Dokumen Jamaah',
                'code' => 'JMD',
                'group_id' => $parent->group_id,
                'group_name' => $parent->group_name,
                'list_no' => 2,
                'icon' => '',
                'route' => '/jamaah/dokumen',
                'isheader' => false,
                'isactive' => true,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('m_modules')->where('code', 'JMD')->delete();
    }
};