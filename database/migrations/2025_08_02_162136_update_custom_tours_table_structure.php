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
        Schema::table('custom_tours', function (Blueprint $table) {
            // Xóa các cột không cần thiết
            $table->dropColumn([
                'start_date',
                'end_date', 
                'num_people',
                'total_price',
                'status'
            ]);
            
            // Thêm các cột mới theo model
            $table->unsignedInteger('destination_id')->after('user_id');
            $table->string('vehicle')->after('destination_id');
            $table->string('duration')->after('vehicle');
            
            // Thêm foreign key cho destination_id
            $table->foreign('destination_id')->references('destination_id')->on('destinations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_tours', function (Blueprint $table) {
            // Xóa foreign key
            $table->dropForeign(['destination_id']);
            
            // Xóa các cột mới
            $table->dropColumn(['destination_id', 'vehicle', 'duration']);
            
            // Thêm lại các cột cũ
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('num_people');
            $table->decimal('total_price', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        });
    }
};
