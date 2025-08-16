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
        Schema::create('promotions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->nullable()->unique('code');
            $table->decimal('discount', 5)->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->nullable()->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->enum('applies_to', ['tour', 'combo', 'hotel', 'transport', 'all'])->nullable();
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')->comment('active = hoạt động, inactive = không hoạt động (ẩn)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
