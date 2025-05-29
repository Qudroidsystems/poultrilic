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
        Schema::create('daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('week_entry_id')->constrained()->onDelete('cascade');
            $table->integer('day_number');
            $table->integer('daily_feeds');
            $table->integer('available_feeds');
            $table->integer('total_feeds_consumed');
            $table->integer('daily_mortality');
            $table->integer('sick_bay');
            $table->integer('total_mortality');
            $table->integer('current_birds');
            $table->string('daily_egg_production'); // Format: e.g. "11 Cr 12PC"
            $table->string('daily_sold_egg');
            $table->string('total_sold_egg');
            $table->integer('broken_egg');
            $table->string('outstanding_egg');
            $table->string('total_egg_in_farm');
            $table->string('drugs')->nullable();
            $table->string('reorder_feeds')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_entries');
    }
};
