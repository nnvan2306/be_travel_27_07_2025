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
        Schema::create('bus_routes', function (Blueprint $table) {
            $table->increments('bus_route_id');
            $table->string('route_name')->nullable();
            $table->string('vehicle_type', 100)->nullable();
            $table->decimal('price', 12)->nullable();
            $table->integer('seats')->nullable();
            $table->string('license_plate', 20)->nullable()->comment('Biển số xe');
            $table->text('description')->nullable()->comment('Mô tả chi tiết về xe');
            $table->decimal('rating', 3, 2)->default(0)->comment('Điểm đánh giá trung bình');
            $table->integer('rating_count')->default(0)->comment('Tổng số lượt đánh giá');
            $table->enum('rental_status', ['available', 'rented'])->default('available')->comment('available = còn trống, rented = đã thuê');
            $table->integer('album_id')->nullable()->index('album_id');
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')->comment('active = hoạt động, inactive = không hoạt động (ẩn)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_routes');
    }
};