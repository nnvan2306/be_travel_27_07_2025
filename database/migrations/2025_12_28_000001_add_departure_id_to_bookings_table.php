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
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('departure_id')->nullable()->index()->after('tour_id')->comment('Liên kết với tour departure cố định');
            $table->foreign('departure_id')->references('departure_id')->on('tour_departures')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['departure_id']);
            $table->dropColumn('departure_id');
        });
    }
};
