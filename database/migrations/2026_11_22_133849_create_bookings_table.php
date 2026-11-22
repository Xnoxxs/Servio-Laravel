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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status')->default('pending');
            $table->rawColumn('price_cents', 'bigint check (price_cents >= 0)');
            $table->char('currency', 3);

            $table->index(['customer_id', 'scheduled_at']);
            $table->index(['service_id', 'status', 'scheduled_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
