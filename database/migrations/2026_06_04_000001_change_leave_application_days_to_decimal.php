<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `days` was integer, truncating half-day leaves (0.5) to 0.
 * Widen to decimal(5,2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->decimal('days', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->integer('days')->nullable()->change();
        });
    }
};
