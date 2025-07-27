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
        Schema::create('guides', function (Blueprint $table) {
            $table->increments('guide_id');
            $table->string('name', 100)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('language', 50)->nullable();
            $table->integer('experience_years')->nullable();
            $table->integer('album_id')->nullable()->index('album_id');
            $table->decimal('price_per_day', 12, 2)->nullable()->comment('Giá thuê theo ngày');
            $table->text('description')->nullable()->comment('Mô tả chi tiết hướng dẫn viên');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại liên hệ');
            $table->string('email', 100)->nullable()->comment('Email liên lạc');
            $table->float('average_rating', 2, 1)->default(0)->comment('Điểm đánh giá trung bình');
            $table->boolean('is_available')->default(true)->comment('Có sẵn để thuê hay không');
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')->comment('active = hoạt động, inactive = không hoạt động (ẩn)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};