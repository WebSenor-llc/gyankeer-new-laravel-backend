<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds proper exit-lifecycle columns to employees:
 *   - date_of_relieving  (DATE)  — last working day, used by payroll for FNF proration
 *   - exit_reason        (VARCHAR) — Resigned / Terminated / Retired / Absconded / Death / Contract End
 *   - notice_served_flag (BOOL)
 *   - exit_notes         (TEXT)
 *
 * The pre-existing column `last_working_day` was auto-generated as a DECIMAL by
 * the schema importer (a known bug in the original CSV import). We leave it
 * alone for backward-compatibility but rely on `date_of_relieving` going forward.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'date_of_relieving')) {
                $table->date('date_of_relieving')->nullable()->after('date_of_joining');
            }
            if (!Schema::hasColumn('employees', 'exit_reason')) {
                $table->string('exit_reason', 60)->nullable()->after('date_of_relieving');
            }
            if (!Schema::hasColumn('employees', 'notice_served_flag')) {
                $table->boolean('notice_served_flag')->default(false)->after('exit_reason');
            }
            if (!Schema::hasColumn('employees', 'exit_notes')) {
                $table->text('exit_notes')->nullable()->after('notice_served_flag');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['date_of_relieving','exit_reason','notice_served_flag','exit_notes'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
