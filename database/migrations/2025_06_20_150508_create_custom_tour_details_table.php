<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_tour_detail', function (Blueprint $table) {
            $table->increments('custom_tour_detail_id');
            $table->unsignedInteger('custom_tour_id');
            $table->unsignedTinyInteger('day')->comment('Thứ tự ngày trong tour, ví dụ: 1 = ngày đầu tiên');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('title', 255);
            $table->text('activity_description')->nullable();
            $table->unsignedInteger('destination_id')->nullable();
            $table->unsignedInteger('guide_id')->nullable();
            $table->unsignedInteger('bus_route_id')->nullable();
            $table->enum('is_deleted', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('custom_tour_id')->references('custom_tour_id')->on('custom_tours')->onDelete('cascade');
            $table->foreign('destination_id')->references('destination_id')->on('destinations')->onDelete('set null');
            $table->foreign('guide_id')->references('guide_id')->on('guides')->onDelete('set null');
            $table->foreign('bus_route_id')->references('bus_route_id')->on('bus_routes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_tour_detail');
    }
};