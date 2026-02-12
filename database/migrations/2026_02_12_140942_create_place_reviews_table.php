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
        Schema::create('place_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->string('source_user_uid');
            $table->string('user_name');
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->dateTime('published_at')->nullable();

            $table->timestamps();

            $table->unique(['place_id', 'source_user_uid']);
            $table->index(['place_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('place_reviews');
    }
};
