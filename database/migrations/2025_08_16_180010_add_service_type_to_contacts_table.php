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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('service_type')->after('id')->nullable();

            // Kiểm tra và thêm các cột khác nếu chưa tồn tại
            if (!Schema::hasColumn('contacts', 'full_name')) {
                $table->string('full_name');
            }

            if (!Schema::hasColumn('contacts', 'email')) {
                $table->string('email');
            }

            if (!Schema::hasColumn('contacts', 'phone')) {
                $table->string('phone');
            }

            if (!Schema::hasColumn('contacts', 'message')) {
                $table->text('message');
            }

            if (!Schema::hasColumn('contacts', 'status')) {
                $table->string('status')->default('pending');
            }

            if (!Schema::hasColumn('contacts', 'is_deleted')) {
                $table->string('is_deleted')->default('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }
};
