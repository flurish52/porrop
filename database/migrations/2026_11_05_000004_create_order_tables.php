<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->text('address');
            $table->string('phone');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // One order = one vendor. A customer buying from two stores makes two orders.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->string('reference')->unique(); // public order code, e.g. PRP-7K2M9QXA

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->string('full_name')->nullable();
            $table->string('guest_phone')->nullable();

            $table->enum('fulfillment_type', ['delivery', 'pickup']);
            $table->foreignId('pickup_point_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('delivery_address')->nullable();

            // pending -> paid -> processing -> ready -> completed (or cancelled)
            $table->string('status')->default('pending');

            // Money. Commission is SNAPSHOTTED here so changing the rate later never rewrites history.
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total_amount', 10, 2);           // what the customer pays
            $table->decimal('commission_rate', 5, 2)->default(0);   // percent, e.g. 10.00
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('vendor_payout', 10, 2)->default(0);
            $table->string('payout_status')->default('pending');    // pending | paid

            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index('payout_status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            // Snapshots, so old orders survive a vendor renaming or deleting a product.
            $table->string('product_name');
            $table->string('variant_name');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('paystack');
            $table->string('reference')->unique(); // our Paystack transaction reference
            $table->string('status')->default('pending'); // pending | success | failed
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('channel')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
    }
};
