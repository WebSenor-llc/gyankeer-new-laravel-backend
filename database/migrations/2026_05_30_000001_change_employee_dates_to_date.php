<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `date_of_confirmation` and `date_of_retirement` were created as decimal(15,2)
 * default 0 (a schema bug) but hold real dates. Convert them to nullable DATE.
 *
 * Existing values are all the meaningless decimal default (0.00), so we drop and
 * re-add rather than cast 0.00 -> DATE (which errors / yields 0000-00-00).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['date_of_confirmation', 'date_of_retirement']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->date('date_of_confirmation')->nullable()->after('probation_end_date');
            $table->date('date_of_retirement')->nullable()->after('service_discontinue');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['date_of_confirmation', 'date_of_retirement']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('date_of_confirmation', 15, 2)->default(0)->after('probation_end_date');
            $table->decimal('date_of_retirement', 15, 2)->default(0)->after('service_discontinue');
        });
    }
};
