<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('given_name');
            $table->string('family_name');
            $table->date('date_of_birth')->nullable();
            $table->foreignId('year_level_id')->nullable()->constrained('year_levels')->nullOnDelete();
            $table->string('nccd_level')->nullable();
            $table->string('nccd_category')->nullable();
            $table->string('primary_disability')->nullable();
            $table->boolean('primary_disability_level_formalised')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
