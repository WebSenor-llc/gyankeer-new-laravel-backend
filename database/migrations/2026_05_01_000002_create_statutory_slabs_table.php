<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single polymorphic table for all statutory rates and slabs.
 *
 * Replaces hardcoded values in config/hreasy.php so an admin can edit
 * EPF rates, ESI caps, PT slabs, LWF amounts, TDS slabs, standard
 * deductions etc. directly from the Settings UI without touching code.
 *
 * Stored under categories with a key:
 *   - 'rate'    e.g. ('rate', 'epf.employee_rate', '12', 2025)
 *   - 'pt'      e.g. ('pt',   'MH.10001-999999.amount', '200', 2025)
 *   - 'lwf'     e.g. ('lwf',  'MH.employee', '25', 2025)
 *   - 'tds'     e.g. ('tds',  'new.0-400000', '0', 2025)
 *
 * The `value_decimal` is the canonical numeric for direct use; `value_json`
 * is for richer entries (slabs with min/max/feb_amount in one row).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('statutory_slabs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('category', 30);          // rate | pt | lwf | tds | bonus | gratuity
            $t->string('key', 100);              // dotted key — see PHPdoc above
            $t->integer('fy_start_year');        // 2025 means FY 2025-26
            $t->decimal('value_decimal', 15, 4)->nullable();
            $t->json('value_json')->nullable();
            $t->string('label', 200)->nullable();
            $t->text('description')->nullable();
            $t->boolean('active_flag')->default(true);
            $t->timestamps();

            $t->unique(['category', 'key', 'fy_start_year'], 'uq_stat_slab');
            $t->index(['category', 'fy_start_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_slabs');
    }
};
