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
        Schema::table('promotions', function (Blueprint $table) {
            // Thêm cột deleted_at nếu chưa có (cho SoftDeletes)
            if (!Schema::hasColumn('promotions', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Drop cột deleted_at nếu có
            if (Schema::hasColumn('promotions', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
