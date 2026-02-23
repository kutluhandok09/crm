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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('currency_code', ['TRY', 'GBP', 'EUR', 'USD'])->default('TRY');
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(20);
            $table->boolean('track_serials')->default(true);
            $table->decimal('stock_quantity', 18, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
