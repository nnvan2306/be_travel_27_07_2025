<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // Xóa foreign key cũ
            $table->dropForeign(['category_id']);
            
            // Thêm foreign key mới
            $table->foreign('category_id')->references('category_id')->on('tour_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // Xóa foreign key mới
            $table->dropForeign(['category_id']);
            
            // Thêm lại foreign key cũ
            $table->foreign('category_id')->references('category_id')->on('destination_categories')->onDelete('set null');
        });
    }
};
