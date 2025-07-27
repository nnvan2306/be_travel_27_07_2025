<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->increments('tour_id');
            $table->unsignedInteger('category_id')->nullable()->index()->comment('Danh mục tour');
            $table->unsignedInteger('album_id')->nullable()->index()->comment('Album ảnh của tour');
            $table->unsignedInteger('guide_id')->nullable()->index()->comment('Hướng dẫn viên phụ trách tour');
            $table->unsignedInteger('bus_route_id')->nullable()->index()->comment('Tuyến xe khách của tour');
            $table->string('tour_name')->comment('Tên tour');
            $table->text('description')->nullable()->comment('Mô tả tour');
            $table->text('itinerary')->nullable()->comment('Hành trình tour');
            $table->string('image')->nullable()->comment('Ảnh đại diện tour');
            $table->decimal('price', 12)->comment('Giá gốc');
            $table->decimal('discount_price', 12)->nullable()->comment('Giá giảm (nếu có)');
            $table->string('duration', 100)->nullable()->comment('Thời lượng tour, ví dụ: 3 ngày 2 đêm');
            $table->enum('status', ['visible', 'hidden'])->nullable()->default('visible')->comment('Trạng thái hiển thị');
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')->comment('active = hoạt động, inactive = không hoạt động (ẩn)');
            $table->string('slug')->unique()->nullable()->comment('Slug để SEO');
            $table->timestamps();

            // Ràng buộc khóa ngoại
            $table->foreign('category_id')->references('category_id')->on('destination_categories')->onDelete('set null');
            $table->foreign('album_id')->references('album_id')->on('albums')->onDelete('set null');
            $table->foreign('guide_id')->references('guide_id')->on('guides')->onDelete('set null');
            $table->foreign('bus_route_id')->references('bus_route_id')->on('bus_routes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};