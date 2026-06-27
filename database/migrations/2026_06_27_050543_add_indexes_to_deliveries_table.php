<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'idx_deliveries_user_status_date');
            $table->index(['tenant_id', 'created_at'], 'idx_deliveries_tenant_date');
            $table->index(['status', 'created_at'], 'idx_deliveries_status_date');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex('idx_deliveries_user_status_date');
            $table->dropIndex('idx_deliveries_tenant_date');
            $table->dropIndex('idx_deliveries_status_date');
        });
    }
};
