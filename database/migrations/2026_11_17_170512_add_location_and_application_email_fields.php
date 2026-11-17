<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_consented_at')->nullable();
        });
        Schema::table('membership_transactions', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_consented_at')->nullable();
            $table->string('application_email_to')->nullable();
            $table->string('application_email_name')->nullable();
            $table->string('application_email_status', 24)->nullable();
            $table->timestamp('application_email_queued_at')->nullable();
            $table->timestamp('application_email_sent_at')->nullable();
            $table->unsignedSmallInteger('application_email_attempts')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_consented_at', 'application_email_to', 'application_email_name', 'application_email_status', 'application_email_queued_at', 'application_email_sent_at', 'application_email_attempts']);
        });
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_consented_at']);
        });
    }
};
