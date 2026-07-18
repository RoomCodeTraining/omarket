<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_available');
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->unsignedInteger('unit_price_cents');
            $table->timestamps();

            $table->unique(['cargo_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_items');
    }
};
