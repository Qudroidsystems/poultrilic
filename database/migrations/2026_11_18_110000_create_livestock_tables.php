<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The livestock builder: the admin defines any kind of farm unit (cattle herd, goat pen, fish pond, piggery...)
 * with its own record fields, then records against it like a flock.
 *
 *  livestock_types   e.g. "Fish ponds" - what a unit is called, what its head count is called
 *  livestock_fields  the record form for that type: label, kind, unit, how it totals, effect on head count
 *  livestock_units   e.g. "Pond 1" - opening count, current count, status
 *  livestock_records one row per unit per date, values keyed by field key
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestock_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // "Fish ponds"
            $table->string('slug')->unique();
            $table->string('unit_label')->default('Unit');  // "Pond", "Herd", "Pen"
            $table->string('count_label')->default('Animals'); // "Fish", "Cattle", "Goats"
            $table->string('icon', 60)->default('ri-leaf-line');
            $table->string('color', 9)->default('#1baf7a');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('livestock_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_type_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);                      // "deaths", "feed_kg"
            $table->string('label');
            $table->string('kind', 12)->default('number');   // number | decimal | money | text | date | select | yes_no
            $table->string('unit', 30)->nullable();          // "kg", "litres", "₦"
            $table->json('options')->nullable();             // choices for select
            $table->string('aggregate', 8)->default('sum');  // sum | avg | last | max | min | count | none
            $table->string('headcount', 8)->default('none'); // none | add | subtract | set
            $table->boolean('required')->default(false);
            $table->string('help')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['livestock_type_id', 'key']);
        });

        Schema::create('livestock_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // "Pond 1"
            $table->string('code', 40)->nullable();
            $table->string('breed')->nullable();
            $table->date('start_date')->nullable();
            $table->unsignedInteger('opening_count')->default(0);
            $table->integer('current_count')->default(0);
            $table->string('status', 12)->default('active');  // active | closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('livestock_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_unit_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->json('values');
            $table->integer('count_after')->nullable();      // head count at end of day (computed)
            $table->text('notes')->nullable();
            $table->string('source', 10)->default('web');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['livestock_unit_id', 'record_date']);
            $table->index('record_date');
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);                      // poultry | livestock
            $table->unsignedBigInteger('target_id')->nullable(); // livestock type id
            $table->string('filename');
            $table->string('stored_path');
            $table->string('status', 12)->default('previewed'); // previewed | imported | failed
            $table->json('summary')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source', 40)->default('poultry'); // poultry | livestock:{type_id}
            $table->json('metrics');
            $table->string('group_by', 10)->default('month');
            $table->string('chart', 10)->default('line');     // line | bar | table
            $table->string('period', 20)->default('last_90');  // last_7 | last_30 | last_90 | this_month | last_month | this_year | all | custom
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->json('filters')->nullable();               // {"flocks": [ids]} or {"units": [ids]}
            $table->boolean('per_entity')->default(false);     // one line per flock / unit
            $table->boolean('pinned')->default(false);         // show on the overview
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('livestock_records');
        Schema::dropIfExists('livestock_units');
        Schema::dropIfExists('livestock_fields');
        Schema::dropIfExists('livestock_types');
    }
};
