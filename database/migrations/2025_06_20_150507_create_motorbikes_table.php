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
        Schema::create('motorbikes', function (Blueprint $table) {
            $table->increments('motorbike_id');
            $table->string('bike_type', 100)->nullable();
            $table->decimal('price_per_day', 12)->nullable();
            $table->string('location')->nullable();
            $table->string('license_plate', 20)->nullable();
            $table->text('description')->nullable();
            $table->float('average_rating', 2, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->enum('rental_status', ['available', 'rented', 'maintenance'])->default('available')->comment('Trạng thái thuê: available = sẵn sàng, rented = đã thuê, maintenance = bảo trì');
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
        Schema::dropIfExists('motorbikes');
    }
};