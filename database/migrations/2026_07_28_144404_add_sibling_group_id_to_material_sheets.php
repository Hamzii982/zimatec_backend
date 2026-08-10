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
        Schema::table('material_sheets', function (Blueprint $table) {
            $table->uuid('sibling_group_id')->nullable()->after('parent_sheet_id');
            $table->index('sibling_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_sheets', function (Blueprint $table) {
            $table->dropIndex('material_sheets_sibling_group_id_index');
            $table->dropColumn('sibling_group_id');
        });
    }
};
