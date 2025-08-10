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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('thumbnail')->nullable()->comment('Ảnh đại diện blog');
            $table->string('location')->nullable()->comment('Địa điểm');
            $table->string('title')->comment('Tiêu đề blog');
            $table->text('description')->nullable()->comment('Mô tả ngắn');
            $table->longText('markdown')->comment('Nội dung markdown');
            $table->string('slug')->unique()->nullable()->comment('Slug để SEO');
            $table->enum('status', ['published', 'draft'])->default('published')->comment('Trạng thái xuất bản');
            $table->integer('view_count')->default(0)->comment('Số lượt xem');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
