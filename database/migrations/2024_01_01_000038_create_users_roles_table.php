<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hreasy by WebSenor — table: users_roles
 *
 * Auto-generated from CSV schema · 23 columns
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users_roles', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->unsignedBigInteger('emp_id')->nullable()->index(); // FK -> employees
            $table->string('username', 100)->nullable()->index();
            $table->string('user_type', 30)->nullable();
            $table->string('role_name', 50)->nullable();
            $table->string('role_code', 30)->nullable();
            $table->text('permissions')->nullable();
            $table->string('login_email', 150)->nullable()->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->string('password_last_changed', 30)->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->integer('login_attempts')->nullable();
            $table->boolean('locked_flag')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->string('last_login_device')->nullable();
            $table->text('data_access_scope')->nullable();
            $table->decimal('allowed_companies', 15, 2)->default(0);
            $table->decimal('allowed_locations', 15, 2)->default(0);
            $table->decimal('allowed_departments', 15, 2)->default(0);
            $table->string('created_by')->nullable();
            $table->boolean('active_flag')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_roles');
    }
};
