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
        Schema::create('chats', function (Blueprint $table) {
            $table->increments('chat_id');
            $table->integer('user_id')->nullable()->index('user_id');
            $table->integer('staff_id')->nullable()->index('staff_id');
            $table->text('message')->nullable();
            $table->enum('sender', ['user', 'staff'])->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->enum('is_deleted', ['active', 'inactive'])->default('active')->comment('active = hoạt động, inactive = không hoạt động (ẩn)');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};