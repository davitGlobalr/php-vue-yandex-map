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
        Schema::table('places', function (Blueprint $table) {
            $table->string('source_url')->nullable()->change();
        });

        Schema::table('place_reviews', function (Blueprint $table) {
            $table->string('source_user_uid')->nullable()->change();
            $table->string('user_name')->nullable()->change();
            $table->unsignedTinyInteger('rating')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
