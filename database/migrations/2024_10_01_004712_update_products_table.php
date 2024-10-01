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
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('detail', 'description'); // Rename column from 'detail' to 'description'
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('cascade'); // Add foreign key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']); // Drop foreign key
            $table->dropColumn('brand_id'); // Drop the column
            $table->renameColumn('description', 'detail'); // Rename back to 'detail'
        });
    }
};
