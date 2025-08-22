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
        // Thêm trường total_seats cho bus_routes nếu chưa có
        if (!Schema::hasColumn('bus_routes', 'total_seats')) {
            Schema::table('bus_routes', function (Blueprint $table) {
                $table->integer('total_seats')->default(0)->after('seats')->comment('Tổng số ghế của xe');
            });
        }

        // Thêm trường total_quantity cho motorbikes nếu chưa có
        if (!Schema::hasColumn('motorbikes', 'total_quantity')) {
            Schema::table('motorbikes', function (Blueprint $table) {
                $table->integer('total_quantity')->default(1)->after('bike_type')->comment('Tổng số xe máy có sẵn');
            });
        }

        // Thêm trường end_date cho bookings nếu chưa có
        if (!Schema::hasColumn('bookings', 'end_date')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->date('end_date')->nullable()->after('start_date')->comment('Ngày kết thúc tour');
            });
        }

        // Thêm trường service_quantity cho bookings để lưu số lượng dịch vụ đã đặt
        if (!Schema::hasColumn('bookings', 'service_quantity')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->integer('service_quantity')->default(1)->after('quantity')->comment('Số lượng dịch vụ đã đặt (xe khách, xe máy)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bus_routes', function (Blueprint $table) {
            $table->dropColumn('total_seats');
        });

        Schema::table('motorbikes', function (Blueprint $table) {
            $table->dropColumn('total_quantity');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'service_quantity']);
        });
    }
};
