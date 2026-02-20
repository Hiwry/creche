<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, float, boolean, json
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }

    /**
     * Seed default settings.
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            // Company settings
            ['key' => 'company_name', 'value' => 'SchoolHub', 'type' => 'string', 'group' => 'company', 'label' => 'Nome da Empresa'],
            ['key' => 'company_cnpj', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'CNPJ'],
            ['key' => 'company_address', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Endereço'],
            ['key' => 'company_phone', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Telefone'],
            ['key' => 'company_email', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'E-mail'],
            ['key' => 'company_logo', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Logo'],
            
            // Financial settings
            ['key' => 'default_monthly_fee', 'value' => '500.00', 'type' => 'float', 'group' => 'financial', 'label' => 'Mensalidade Padrão'],
            ['key' => 'default_material_fee', 'value' => '300.00', 'type' => 'float', 'group' => 'financial', 'label' => 'Taxa de Material Padrão'],
            ['key' => 'extra_hour_rate', 'value' => '15.00', 'type' => 'float', 'group' => 'financial', 'label' => 'Valor da Hora Extra'],
            ['key' => 'extra_hour_tolerance', 'value' => '10', 'type' => 'integer', 'group' => 'financial', 'label' => 'Tolerância (minutos)'],
            ['key' => 'payment_due_day', 'value' => '10', 'type' => 'integer', 'group' => 'financial', 'label' => 'Dia de Vencimento'],
            
            // Invoice settings
            ['key' => 'invoice_footer', 'value' => 'Obrigado pela preferência!', 'type' => 'string', 'group' => 'invoice', 'label' => 'Rodapé da Fatura'],
            ['key' => 'invoice_bank_info', 'value' => '', 'type' => 'string', 'group' => 'invoice', 'label' => 'Dados Bancários'],
            ['key' => 'invoice_pix_key', 'value' => '', 'type' => 'string', 'group' => 'invoice', 'label' => 'Chave PIX'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
