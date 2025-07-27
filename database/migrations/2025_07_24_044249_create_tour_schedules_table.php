<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tour_schedules', function (Blueprint $table) {
            $table->increments('schedule_id');
            $table->unsignedInteger('tour_id')->index()->comment('Tour liên kết');
            $table->unsignedTinyInteger('day')->comment('Thứ tự ngày trong tour, ví dụ: 1 = ngày đầu tiên');
            $table->time('start_time')->nullable()->comment('Giờ bắt đầu hoạt động');
            $table->time('end_time')->nullable()->comment('Giờ kết thúc hoạt động');
            $table->string('title')->comment('Tên hoạt động chính, ví dụ: Tham quan phố cổ');
            $table->text('activity_description')->nullable()->comment('Mô tả chi tiết hoạt động');
            $table->unsignedInteger('destination_id')->nullable()->index()->comment('Liên kết điểm đến cụ thể trong hoạt động');
            $table->timestamps();

            // Ràng buộc khóa ngoại
            $table->foreign('tour_id')->references('tour_id')->on('tours')->onDelete('cascade');
            $table->foreign('destination_id')->references('destination_id')->on('destinations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_schedules');
    }
};