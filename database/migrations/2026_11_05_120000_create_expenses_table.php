<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            // Null = a farm-wide expense not tied to one flock (e.g. electricity).
            $table->foreignId('flock_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expense_date');
            $table->string('category', 40);
            $table->string('description')->nullable();
            $table->string('vendor')->nullable();
            $table->decimal('amount', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_date');
            $table->index(['flock_id', 'expense_date']);
            $table->index(['category', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
