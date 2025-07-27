<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tour_destinations', function (Blueprint $table) {
            $table->unsignedInteger('tour_id');
            $table->unsignedInteger('destination_id');

            $table->primary(['tour_id', 'destination_id']);

            $table->foreign('tour_id')->references('tour_id')->on('tours')->onDelete('cascade');
            $table->foreign('destination_id')->references('destination_id')->on('destinations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_destinations');
    }
};