<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->increments('destination_id');
            $table->unsignedBigInteger('category_id');
            $table->integer('album_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('area', 100)->nullable();
            $table->string('img_banner')->nullable(); // Đường dẫn ảnh banner lưu trong storage/app/public
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')->comment('active = hiển thị, inactive = ẩn');
            $table->string('slug')->unique()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};