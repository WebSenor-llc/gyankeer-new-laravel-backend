<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Effective-dated PF / EPS / ESI applicability history per employee.
 * Latest row with effective_from <= period start decides applicability;
 * employees.*_applicable_flag mirrors the current state.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('employee_statutory_changes')) return;

        Schema::create('employee_statutory_changes', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('emp_id')->index();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->enum('scheme', ['pf', 'eps', 'esi']);
            $t->enum('action', ['removed', 'restored']);
            $t->date('effective_from');
            $t->string('reason', 255)->nullable();
            $t->unsignedBigInteger('changed_by')->nullable();
            $t->timestamps();
            $t->index(['emp_id', 'scheme', 'effective_from'], 'esc_emp_scheme_from_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_statutory_changes');
    }
};
