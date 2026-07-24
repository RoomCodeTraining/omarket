<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_request_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('budget_cents')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (! Schema::hasTable('custom_requests')) {
            return;
        }

        $now = now();

        DB::table('custom_requests')->orderBy('id')->get()->each(function (object $request) use ($now): void {
            DB::table('custom_request_items')->insert([
                'custom_request_id' => $request->id,
                'label' => filled($request->title) ? $request->title : 'Produit',
                'quantity' => max(1, (int) $request->quantity),
                'budget_cents' => $request->budget_cents,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_request_items');
    }
};
