<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // One short line shown on the stores page and in link previews.
            $table->string('tagline', 100)->nullable()->after('description');
            // Vendors can opt out of the public stores page and keep a private link.
            $table->boolean('listed_in_directory')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'listed_in_directory']);
        });
    }
};
