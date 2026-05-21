<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Widens `esi_records.days_worked` from INT to DECIMAL(8,2) so half-day
 * attendance (e.g. 5.5, 12.5) stays intact when feeding the ESIC return.
 *
 * Leaves and half-days are real fractional values — they shouldn't be silently
 * cast to whole numbers anywhere in the pipeline. Only rupee amounts get
 * rounded, and that's done at display time.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('esi_records')) return;

        // doctrine/dbal not required — go straight to raw SQL for MySQL
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `esi_records` MODIFY `days_worked` DECIMAL(8,2) NULL DEFAULT NULL");
        } else {
            // Fallback (sqlite/pgsql in tests) — recreate column
            Schema::table('esi_records', function (Blueprint $t) {
                $t->decimal('days_worked_new', 8, 2)->nullable()->after('days_worked');
            });
            DB::statement("UPDATE esi_records SET days_worked_new = days_worked");
            Schema::table('esi_records', fn (Blueprint $t) => $t->dropColumn('days_worked'));
            Schema::table('esi_records', fn (Blueprint $t) => $t->renameColumn('days_worked_new', 'days_worked'));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('esi_records')) return;
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `esi_records` MODIFY `days_worked` INT NULL DEFAULT NULL");
        }
    }
};
