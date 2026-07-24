<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->boolean('has_supplier')->default(false)->after('budget_cents');
            $table->string('supplier_name')->nullable()->after('has_supplier');
            $table->string('supplier_contact')->nullable()->after('supplier_name');
            $table->foreignId('cargo_id')
                ->nullable()
                ->after('supplier_contact')
                ->constrained()
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('status');
            $table->timestamp('cargo_assigned_at')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_id');
            $table->dropColumn([
                'has_supplier',
                'supplier_name',
                'supplier_contact',
                'validated_at',
                'cargo_assigned_at',
            ]);
        });
    }
};
