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
        Schema::create('tour_departures', function (Blueprint $table) {
            $table->increments('departure_id');
            $table->unsignedInteger('tour_id')->index()->comment('Tour liên kết');
            $table->date('departure_date')->comment('Ngày khởi hành cố định');
            $table->decimal('price', 12, 2)->comment('Giá tour cho ngày khởi hành này');
            $table->integer('max_capacity')->default(50)->comment('Số lượng khách tối đa cho chuyến đi này');
            $table->integer('booked_count')->default(0)->comment('Số lượng khách đã đặt');
            $table->enum('status', ['available', 'full', 'cancelled'])->default('available')->comment('Trạng thái chuyến đi');
            $table->text('notes')->nullable()->comment('Ghi chú cho chuyến đi');
            $table->enum('is_deleted', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Ràng buộc khóa ngoại
            $table->foreign('tour_id')->references('tour_id')->on('tours')->onDelete('cascade');
            
            // Index để tối ưu query
            $table->index(['tour_id', 'departure_date']);
            $table->index(['departure_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_departures');
    }
};
