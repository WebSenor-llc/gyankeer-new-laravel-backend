<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirror SUGAM HR's Incentive (Manual) columns: store the per-hour rate, ESI
 * deduction on OT amount, and the resulting Payable amount.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_records', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->default(0)->after('ot_rate');
            }
            if (!Schema::hasColumn('overtime_records', 'ot_esi')) {
                $table->decimal('ot_esi', 10, 2)->default(0)->after('ot_amount');
            }
            if (!Schema::hasColumn('overtime_records', 'ot_payable')) {
                $table->decimal('ot_payable', 12, 2)->default(0)->after('ot_esi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            foreach (['hourly_rate','ot_esi','ot_payable'] as $col) {
                if (Schema::hasColumn('overtime_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
