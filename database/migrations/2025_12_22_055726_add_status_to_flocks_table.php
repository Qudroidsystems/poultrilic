<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flocks', function (Blueprint $table) {
            // Add name column
            if (!Schema::hasColumn('flocks', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            
            // Add breed column
            if (!Schema::hasColumn('flocks', 'breed')) {
                $table->string('breed')->nullable()->after('name');
            }
            
            // Add date_of_arrival column
            if (!Schema::hasColumn('flocks', 'date_of_arrival')) {
                $table->date('date_of_arrival')->nullable()->after('current_bird_count');
            }
            
            // Add age_in_weeks column
            if (!Schema::hasColumn('flocks', 'age_in_weeks')) {
                $table->integer('age_in_weeks')->default(0)->after('date_of_arrival');
            }
            
            // Add status column
            if (!Schema::hasColumn('flocks', 'status')) {
                $table->enum('status', ['active', 'inactive', 'sold', 'ended'])
                      ->default('active')
                      ->after('age_in_weeks');
            }
        });
        
        // Set default names for existing flocks if name is null
        \DB::table('flocks')->whereNull('name')->update([
            'name' => \DB::raw("CONCAT('Flock ', id)"),
            'breed' => 'Layers',
            'status' => 'active'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flocks', function (Blueprint $table) {
            $columns = ['name', 'breed', 'date_of_arrival', 'age_in_weeks', 'status'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('flocks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};