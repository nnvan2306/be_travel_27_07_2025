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
        Schema::table('promotions', function (Blueprint $table) {
            // Thêm các cột còn thiếu
            if (!Schema::hasColumn('promotions', 'min_order_amount')) {
                $table->decimal('min_order_amount', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('promotions', 'max_discount_amount')) {
                $table->decimal('max_discount_amount', 15, 2)->nullable();
            }

            // Kiểm tra và thêm các cột khác nếu cần
            if (!Schema::hasColumn('promotions', 'max_uses')) {
                $table->integer('max_uses')->nullable();
            }

            if (!Schema::hasColumn('promotions', 'used_count')) {
                $table->integer('used_count')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Chỉ drop các cột mới thêm, không drop các cột đã có sẵn
            if (Schema::hasColumn('promotions', 'min_order_amount')) {
                $table->dropColumn('min_order_amount');
            }
            if (Schema::hasColumn('promotions', 'max_discount_amount')) {
                $table->dropColumn('max_discount_amount');
            }
            // Không drop max_uses và used_count vì chúng đã có sẵn trong bảng gốc
        });
    }
};
