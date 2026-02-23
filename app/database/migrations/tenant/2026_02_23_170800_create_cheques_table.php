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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('portfolio_no')->unique();
            $table->enum('type', ['received', 'issued']);
            $table->string('counterparty_name');
            $table->decimal('amount', 18, 4);
            $table->enum('currency_code', ['TRY', 'GBP', 'EUR', 'USD'])->default('TRY');
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['portfolio', 'endorsed', 'collected', 'bounced', 'cancelled'])->default('portfolio');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
