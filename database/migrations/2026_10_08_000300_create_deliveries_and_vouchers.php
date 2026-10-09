<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 20);
            $table->string('driver_name')->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->index();
            $table->string('recipient_name');
            $table->string('recipient_document', 40)->nullable();
            $table->string('destination_address')->nullable();
            $table->string('destination_phone', 30)->nullable();
            $table->timestamp('released_at');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('proof_note')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_units', function (Blueprint $table) {
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_unit_id')->constrained()->restrictOnDelete();
            $table->primary(['delivery_id', 'storage_unit_id']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('delivery_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('total', 12, 2);
            $table->string('status', 20)->default('pendiente')->index();
            $table->timestamp('issued_at');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('concept', 20);
            $table->string('description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_lines');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('delivery_units');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('carriers');
    }
};
