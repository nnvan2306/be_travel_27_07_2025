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
                $table->decimal('min_order_amount', 15, 2)->nullable()->after('description');
            }

            if (!Schema::hasColumn('promotions', 'max_discount_amount')) {
                $table->decimal('max_discount_amount', 15, 2)->nullable()->after('min_order_amount');
            }

            // Kiểm tra và thêm các cột khác nếu cần
            if (!Schema::hasColumn('promotions', 'max_uses')) {
                $table->integer('max_uses')->nullable()->after('end_date');
            }

            if (!Schema::hasColumn('promotions', 'used_count')) {
                $table->integer('used_count')->default(0)->after('max_uses');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'min_order_amount',
                'max_discount_amount',
                'max_uses',
                'used_count'
            ]);
        });
    }
};
