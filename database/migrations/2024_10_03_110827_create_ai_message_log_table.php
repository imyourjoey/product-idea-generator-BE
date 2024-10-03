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
        Schema::create('ai_message_logs', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('answer');
            $table->foreignId('generated_product_idea_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_message_logs', function (Blueprint $table) {
            $table->dropForeign(['generated_product_idea_id']);
        });
        Schema::dropIfExists('ai_message_logs');
    }
};
