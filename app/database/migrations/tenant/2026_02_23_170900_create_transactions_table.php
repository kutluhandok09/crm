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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_no')->unique();
            $table->date('txn_date');
            $table->enum('type', [
                'cash_in',
                'cash_out',
                'bank_in',
                'bank_out',
                'transfer',
                'cheque_receive',
                'cheque_issue',
                'cheque_collection',
                'cheque_bounce',
            ]);
            $table->enum('currency_code', ['TRY', 'GBP', 'EUR', 'USD'])->default('TRY');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('amount', 18, 4);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('destination_type')->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cheque_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
