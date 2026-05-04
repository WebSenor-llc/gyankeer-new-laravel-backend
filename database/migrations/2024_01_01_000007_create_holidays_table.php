<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: holidays
 *
 * Auto-generated from CSV schema · 13 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->bigIncrements('holiday_id');
            $table->date('holiday_date')->nullable();
            $table->string('holiday_name')->nullable();
            $table->string('holiday_type')->nullable();
            $table->string('applicable_states')->nullable();
            $table->string('applicable_locations')->nullable();
            $table->boolean('optional_flag')->default(false);
            $table->string('fy_year')->nullable();
            $table->string('declared_by')->nullable();
            $table->string('gazette_ref')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
