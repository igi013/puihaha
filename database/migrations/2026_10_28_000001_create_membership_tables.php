<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('membership_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('fee', 10, 2)->nullable();
            $table->unsignedInteger('validity_months')->nullable();
            $table->text('benefits')->nullable();
            $table->timestamps();
        });
        foreach (['Student', 'Regular', 'Senior Citizen', 'Corporate'] as $name) {
            DB::table('membership_categories')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['Basic', 'Silver', 'Gold', 'Platinum'] as $name) {
            DB::table('membership_levels')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number', 40)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('membership_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_level_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
        Schema::dropIfExists('membership_levels');
        Schema::dropIfExists('membership_categories');
    }
};
