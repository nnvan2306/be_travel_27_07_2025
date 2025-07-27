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
        Schema::create('bookings', function (Blueprint $table) {
            $table->increments('booking_id');
            $table->unsignedBigInteger('user_id')->index();

            // Các liên kết dịch vụ (nullable)
            $table->unsignedInteger('tour_id')->nullable()->index();
            $table->unsignedInteger('guide_id')->nullable()->index();
            $table->unsignedInteger('hotel_id')->nullable()->index();
            $table->unsignedInteger('bus_route_id')->nullable()->index();
            $table->unsignedInteger('motorbike_id')->nullable()->index();
            $table->unsignedInteger('custom_tour_id')->nullable()->index();

            // Booking info
            $table->integer('quantity')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('total_price', 12, 2);

            // Trạng thái & lý do huỷ
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('cancel_reason')->nullable();

            $table->enum('is_deleted', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tour_id')->references('tour_id')->on('tours')->nullOnDelete();
            $table->foreign('guide_id')->references('guide_id')->on('guides')->nullOnDelete();
            $table->foreign('hotel_id')->references('hotel_id')->on('hotels')->nullOnDelete();
            $table->foreign('bus_route_id')->references('bus_route_id')->on('bus_routes')->nullOnDelete();
            $table->foreign('motorbike_id')->references('motorbike_id')->on('motorbikes')->nullOnDelete();
            $table->foreign('custom_tour_id')->references('custom_tour_id')->on('custom_tours')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};