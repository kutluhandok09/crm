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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->enum('type', ['sale', 'purchase', 'sale_return', 'purchase_return']);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->enum('currency_code', ['TRY', 'GBP', 'EUR', 'USD'])->default('TRY');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('vat_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
