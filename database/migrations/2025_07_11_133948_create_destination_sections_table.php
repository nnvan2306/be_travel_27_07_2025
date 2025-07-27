<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('destination_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('destination_id')->index();
            $table->string('type', 50); // 'intro', 'experience', 'highlight', ...
            $table->string('title')->nullable();
            $table->text('content')->nullable(); // json cho highlight, gallery, delicacies
            $table->timestamps();

            $table->foreign('destination_id')->references('destination_id')->on('destinations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_sections');
    }
};