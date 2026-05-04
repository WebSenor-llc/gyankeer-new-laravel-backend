<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: designations
 *
 * Auto-generated from CSV schema · 21 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->bigIncrements('designation_id');
            $table->string('designation_code')->nullable();
            $table->string('designation_name')->nullable();
            $table->string('grade')->nullable();
            $table->string('level')->nullable();
            $table->string('band')->nullable();
            $table->unsignedBigInteger('dept_id')->nullable()->index(); // FK -> departments
            $table->unsignedBigInteger('reports_to_designation_id')->nullable()->index(); // FK -> designations
            $table->string('job_family')->nullable();
            $table->string('job_function')->nullable();
            $table->string('job_category')->nullable();
            $table->decimal('min_gross', 15, 2)->default(0);
            $table->decimal('max_gross', 15, 2)->default(0);
            $table->decimal('min_basic', 15, 2)->default(0);
            $table->string('exempt_from_overtime')->nullable();
            $table->boolean('people_manager_flag')->default(false);
            $table->boolean('apprentice_flag')->default(false);
            $table->boolean('contract_flag')->default(false);
            $table->boolean('active_flag')->default(false);
            $table->date('effective_from')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
