<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kept for environments that already ran an earlier version of this migration.
     * Fresh installs get guest fields from create_custom_requests_table.
     */
    public function up(): void
    {
        if (! Schema::hasTable('custom_requests')) {
            return;
        }

        Schema::table('custom_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_requests', 'guest_name')) {
                $table->string('guest_name')->nullable();
            }

            if (! Schema::hasColumn('custom_requests', 'guest_email')) {
                $table->string('guest_email')->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
