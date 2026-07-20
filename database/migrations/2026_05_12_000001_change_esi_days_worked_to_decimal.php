<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL-only DDL — sqlite (test suite) already has a decimal column here.
        if (DB::getDriverName() !== 'mysql') return;
        DB::statement("ALTER TABLE esi_records MODIFY days_worked DECIMAL(5,1) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;
        DB::statement("ALTER TABLE esi_records MODIFY days_worked INT NULL DEFAULT NULL");
    }
};
