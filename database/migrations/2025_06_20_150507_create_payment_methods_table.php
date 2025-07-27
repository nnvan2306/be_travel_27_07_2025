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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->increments('payment_method_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')
                ->comment('active = hoạt động, inactive = không hoạt động (ẩn)');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};