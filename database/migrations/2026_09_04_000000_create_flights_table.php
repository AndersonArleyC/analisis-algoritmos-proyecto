<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('airline');
            $table->string('flight_code');
            $table->string('origin', 3);
            $table->string('destination', 3);
            // Horas locales de America/Bogota, sin conversión implícita a UTC.
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at');
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('stops');
            $table->string('baggage_description');
            $table->unsignedInteger('total_price_cop');

            $table->unique(['flight_code', 'departure_at']);
            $table->index(['origin', 'destination', 'departure_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
