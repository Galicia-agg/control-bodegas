<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->string('service', 20)->index();
            $table->string('unit_type', 20)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('free_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
