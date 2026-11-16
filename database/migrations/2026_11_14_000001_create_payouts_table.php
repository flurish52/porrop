<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per transfer you make to a vendor: the audit trail for "did I pay them?"
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('order_count');
            $table->string('reference')->nullable();   // your bank transfer reference
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();
        });

        // Which payout settled each order.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('payout_id')->nullable()->after('payout_status')->constrained('payouts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_id');
        });

        Schema::dropIfExists('payouts');
    }
};
