<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('cargo_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
            $table->boolean('listed_in_shop')
                ->default(false)
                ->after('is_featured');
            $table->timestamp('warehouse_deposited_at')
                ->nullable()
                ->after('listed_in_shop');
            $table->timestamp('deposit_reminder_sent_at')
                ->nullable()
                ->after('warehouse_deposited_at');
        });

        // Existing Ôhéfê catalogue products stay visible in the boutique.
        DB::table('products')
            ->whereNull('user_id')
            ->update(['listed_in_shop' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_id');
            $table->dropColumn([
                'listed_in_shop',
                'warehouse_deposited_at',
                'deposit_reminder_sent_at',
            ]);
        });
    }
};
