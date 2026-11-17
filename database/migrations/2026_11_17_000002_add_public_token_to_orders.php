<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Long random key for the customer's order link. The short PRP- reference is only a label now.
            $table->string('public_token', 40)->nullable()->after('reference');
        });

        // Orders that already exist get a token too.
        DB::table('orders')->whereNull('public_token')->orderBy('id')->each(function ($order) {
            DB::table('orders')->where('id', $order->id)->update(['public_token' => Str::random(32)]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
