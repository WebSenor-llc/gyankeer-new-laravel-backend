<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds PH (Paid Holiday) count to attendance_summary.
 * PH = company-declared paid holiday (Republic Day, Independence Day,
 * Diwali, etc.). For contract workers, PH counts as a PAID day in the
 * daily-wage formula:
 *    final_salary = monthly × (P + PH + 0.5×HD) ÷ payable_days
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('attendance_summary') && !Schema::hasColumn('attendance_summary', 'ph_count')) {
            Schema::table('attendance_summary', function (Blueprint $table) {
                $table->decimal('ph_count', 6, 2)->default(0)->after('hd_count');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_summary', 'ph_count')) {
            Schema::table('attendance_summary', function (Blueprint $table) {
                $table->dropColumn('ph_count');
            });
        }
    }
};
