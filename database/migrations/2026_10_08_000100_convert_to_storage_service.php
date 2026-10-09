<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the generic inventory model with customer storage: customers,
     * receipts (ingresos) and the storage units that belong to each receipt.
     */
    public function up(): void
    {
        Schema::dropIfExists('movement_items');
        Schema::dropIfExists('movements');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('categories');

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_id', 30)->nullable()->unique();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('transport_mode', 20);
            $table->string('transport_document', 60)->nullable();
            $table->string('duca', 60)->nullable();
            $table->string('carrier')->nullable();
            $table->string('origin')->nullable();
            $table->string('status', 20)->default('activo')->index();
            $table->timestamp('received_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('storage_units')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('pieces')->nullable();
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->decimal('volume_m3', 10, 3)->nullable();
            $table->string('location', 60)->nullable();
            $table->string('status', 20)->default('almacenado')->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_units');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('customers');
    }
};
