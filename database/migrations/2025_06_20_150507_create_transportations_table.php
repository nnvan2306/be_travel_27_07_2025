<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transportations', function (Blueprint $table) {
            $table->increments('transportation_id');
            $table->string('type', 100)->nullable();
            $table->string('name', 100)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('album_id')->nullable()->index();
            $table->boolean('is_available')->default(true);
            $table->integer('capacity')->nullable();
            $table->text('description')->nullable();
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')->comment('active = hoạt động, inactive = không hoạt động (ẩn)');
            $table->timestamps();

            $table->foreign('album_id')->references('album_id')->on('albums')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportations');
    }
};