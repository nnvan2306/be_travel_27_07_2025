<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Thêm cột discount_type nếu chưa có
            if (!Schema::hasColumn('promotions', 'discount_type')) {
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage')->after('code');
            }
            
            // Thêm cột discount_value nếu chưa có
            if (!Schema::hasColumn('promotions', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->after('discount_type');
            }
            
            // Thêm cột start_date nếu chưa có
            if (!Schema::hasColumn('promotions', 'start_date')) {
                $table->timestamp('start_date')->nullable()->after('discount_value');
            }
            
            // Thêm cột end_date nếu chưa có
            if (!Schema::hasColumn('promotions', 'end_date')) {
                $table->timestamp('end_date')->nullable()->after('start_date');
            }
            
            // Thêm cột max_uses nếu chưa có
            if (!Schema::hasColumn('promotions', 'max_uses')) {
                $table->integer('max_uses')->nullable()->after('end_date');
            }
            
            // Thêm cột current_uses nếu chưa có
            if (!Schema::hasColumn('promotions', 'current_uses')) {
                $table->integer('current_uses')->default(0)->after('max_uses');
            }
            
            // Thêm cột is_active nếu chưa có
            if (!Schema::hasColumn('promotions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('current_uses');
            }
            
            // Thêm cột description nếu chưa có
            if (!Schema::hasColumn('promotions', 'description')) {
                $table->text('description')->nullable()->after('is_active');
            }
            
            // Thêm cột created_at nếu chưa có
            if (!Schema::hasColumn('promotions', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('description');
            }
            
            // Thêm cột updated_at nếu chưa có
            if (!Schema::hasColumn('promotions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Chỉ drop các cột mới thêm
            $columnsToDrop = [
                'discount_type',
                'discount_value', 
                'start_date',
                'end_date',
                'max_uses',
                'current_uses',
                'is_active',
                'description',
                'created_at',
                'updated_at'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('promotions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
