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
        Schema::create('invoice_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_serial_id')->constrained('product_serials')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['invoice_item_id', 'product_serial_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_item_serials');
    }
};
