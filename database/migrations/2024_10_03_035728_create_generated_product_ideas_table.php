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
        Schema::create('generated_product_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('category');
            $table->text('description');
            $table->text('unique_selling_point');
            $table->string('target_market');
            $table->decimal('estimated_cost', 10, 2);
            $table->decimal('estimated_selling_price', 10, 2);
            $table->integer('estimated_units_sold_per_month');
            $table->integer('feasibility_score');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // user_id must exist and is required
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('cascade');  // brand_id can be null
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_product_ideas');
    }
};
