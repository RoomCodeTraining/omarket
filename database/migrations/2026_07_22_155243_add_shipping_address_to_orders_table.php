<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_phone')->nullable()->after('guest_email');
            $table->string('shipping_line1')->nullable()->after('shipping_phone');
            $table->string('shipping_line2')->nullable()->after('shipping_line1');
            $table->string('shipping_city')->nullable()->after('shipping_line2');
            $table->string('shipping_province', 80)->nullable()->after('shipping_city');
            $table->string('shipping_postal_code', 20)->nullable()->after('shipping_province');
            $table->string('shipping_country', 2)->default('CA')->after('shipping_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_phone',
                'shipping_line1',
                'shipping_line2',
                'shipping_city',
                'shipping_province',
                'shipping_postal_code',
                'shipping_country',
            ]);
        });
    }
};
