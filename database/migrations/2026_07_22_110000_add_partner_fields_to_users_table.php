<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Client->value)->after('email');
            $table->boolean('can_publish')->default(false)->after('role');
            $table->timestamp('partner_approved_at')->nullable()->after('can_publish');
            $table->index('role');
        });

        if (Schema::hasColumn('users', 'is_admin')) {
            DB::table('users')->where('is_admin', true)->update([
                'role' => UserRole::Admin->value,
                'can_publish' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'can_publish', 'partner_approved_at']);
        });
    }
};
