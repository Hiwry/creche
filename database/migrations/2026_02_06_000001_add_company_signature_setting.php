<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $exists = DB::table('settings')->where('key', 'company_signature')->exists();
        if (!$exists) {
            DB::table('settings')->insert([
                'key' => 'company_signature',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Assinatura (PNG)',
                'description' => 'Assinatura usada no recibo (preferencialmente PNG transparente).',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->where('key', 'company_signature')->delete();
    }
};
