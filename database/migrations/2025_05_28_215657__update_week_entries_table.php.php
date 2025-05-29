<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWeekEntriesTable extends Migration
{
    public function up()
    {
        Schema::table('week_entries', function (Blueprint $table) {
            $table->string('week_name')->after('flock_id');
            $table->dropColumn('week_number');
        });
    }

    public function down()
    {
        Schema::table('week_entries', function (Blueprint $table) {
            $table->integer('week_number');
            $table->dropColumn('week_name');
        });
    }
}