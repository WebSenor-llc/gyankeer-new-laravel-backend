<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes columns that were inadvertently created as VARCHAR when they should
 * have been DECIMAL — found during the database audit on 2026-05-01.
 *
 * Affected:
 *   - employees.current_conv  (varchar → decimal(15,2))
 *   - employees.current_med   (varchar → decimal(15,2))
 *   - payslips.payable_days   (varchar → decimal(8,2))
 *   - payslips.pt             (varchar → decimal(15,2))   already decimal? safe to re-cast
 *   - payslips.holidays       (varchar → decimal(8,2))
 *
 * Existing string values are first sanitised (empty → NULL, then cast).
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. employees.current_conv & current_med
        DB::statement("UPDATE employees SET current_conv = NULL WHERE current_conv = '' OR current_conv NOT REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'");
        DB::statement("UPDATE employees SET current_med  = NULL WHERE current_med  = '' OR current_med  NOT REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'");
        DB::statement("ALTER TABLE employees MODIFY current_conv DECIMAL(15,2) NULL DEFAULT NULL");
        DB::statement("ALTER TABLE employees MODIFY current_med  DECIMAL(15,2) NULL DEFAULT NULL");

        // 2. payslips.payable_days, .pt, .holidays
        DB::statement("UPDATE payslips SET payable_days = NULL WHERE payable_days = '' OR payable_days NOT REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'");
        DB::statement("UPDATE payslips SET pt           = NULL WHERE pt           = '' OR pt           NOT REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'");
        DB::statement("UPDATE payslips SET holidays     = NULL WHERE holidays     = '' OR holidays     NOT REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'");
        DB::statement("ALTER TABLE payslips MODIFY payable_days DECIMAL(8,2)  NULL DEFAULT NULL");
        DB::statement("ALTER TABLE payslips MODIFY pt           DECIMAL(15,2) NULL DEFAULT NULL");
        DB::statement("ALTER TABLE payslips MODIFY holidays     DECIMAL(8,2)  NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY current_conv VARCHAR(50) NULL");
        DB::statement("ALTER TABLE employees MODIFY current_med  VARCHAR(50) NULL");
        DB::statement("ALTER TABLE payslips  MODIFY payable_days VARCHAR(50) NULL");
        DB::statement("ALTER TABLE payslips  MODIFY pt           VARCHAR(50) NULL");
        DB::statement("ALTER TABLE payslips  MODIFY holidays     VARCHAR(50) NULL");
    }
};
