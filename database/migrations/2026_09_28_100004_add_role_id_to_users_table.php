<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });

        $legacyRoles = DB::table('users')->select('id', 'role')->get();

        foreach ($legacyRoles as $user) {
            $roleName = match ($user->role) {
                'admin' => 'super_admin',
                default => 'user',
            };

            $roleId = DB::table('roles')->where('name', $roleName)->value('id');

            if ($roleId) {
                DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
