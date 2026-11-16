<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // /store/{slug}
            $table->text('description')->nullable();
            $table->string('logo_public_id')->nullable();   // Cloudinary
            $table->string('banner_public_id')->nullable(); // Cloudinary

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable(); // digits, 234XXXXXXXXXX
            $table->json('socials')->nullable();

            $table->string('status')->default('pending')->index(); // pending | active | suspended

            // Payouts (manual in v1: you pay vendors from the admin payouts list)
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('paystack_subaccount_code')->nullable(); // for automatic splits later

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('owner'); // owner | staff
            $table->timestamps();

            $table->unique(['vendor_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_user');
        Schema::dropIfExists('vendors');
    }
};
