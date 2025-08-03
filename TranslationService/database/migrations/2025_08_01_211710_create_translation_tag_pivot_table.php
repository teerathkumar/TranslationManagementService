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
        Schema::create('translation_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_id')->constrained()->onDelete('cascade');
            $table->foreignId('translation_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate relationships
            $table->unique(['translation_id', 'translation_tag_id']);
            
            // Indexes for efficient queries
            $table->index(['translation_id']);
            $table->index(['translation_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_tag_pivot');
    }
};
