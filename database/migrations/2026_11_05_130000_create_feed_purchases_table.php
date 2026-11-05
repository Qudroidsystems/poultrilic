<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date');
            $table->decimal('bags', 10, 2);
            // 0 for opening stock that was already on the farm (no expense is created for it).
            $table->decimal('cost_per_bag', 12, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->string('notes')->nullable();
            // The expense row created from this purchase, kept in step on edit/delete.
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_purchases');
    }
};
