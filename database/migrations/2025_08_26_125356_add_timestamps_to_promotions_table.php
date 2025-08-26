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
            // Thêm cột created_at nếu chưa có
            if (!Schema::hasColumn('promotions', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            
            // Thêm cột updated_at nếu chưa có
            if (!Schema::hasColumn('promotions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Chỉ drop các cột mới thêm
            if (Schema::hasColumn('promotions', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('promotions', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
