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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->morphs('payable'); // payable_type, payable_id (MonthlyFee, MaterialFee, etc.)
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'pix', 'credit_card', 'debit_card', 'bank_transfer', 'other'])->default('pix');
            $table->date('payment_date');
            $table->string('receipt_path')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
