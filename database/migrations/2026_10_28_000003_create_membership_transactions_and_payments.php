<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->date('membership_starts_at')->nullable();
            $table->date('membership_expires_at')->nullable();
        });
        Schema::create('membership_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_level_id')->constrained()->restrictOnDelete();
            $table->string('category_name');
            $table->string('level_name');
            $table->text('benefits')->nullable();
            $table->string('type', 20);
            $table->string('status', 20)->default('Pending')->index();
            $table->decimal('amount_due', 10, 2);
            $table->unsignedInteger('validity_months');
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['member_id', 'status']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 40)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('membership_transaction_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 30);
            $table->string('external_reference', 100)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('membership_transactions');
        Schema::table('members', fn (Blueprint $table) => $table->dropColumn(['membership_starts_at', 'membership_expires_at']));
    }
};
