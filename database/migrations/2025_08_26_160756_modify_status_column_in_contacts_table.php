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
        Schema::table('contacts', function (Blueprint $table) {
            // Thay đổi cột status từ VARCHAR nhỏ thành VARCHAR lớn hơn để chứa các giá trị như 'pending'
            $table->string('status', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Khôi phục cột status về kích thước ban đầu
            $table->string('status', 20)->change();
        });
    }
};
