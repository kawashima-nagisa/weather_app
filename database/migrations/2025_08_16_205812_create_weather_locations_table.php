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
        Schema::create('weather_locations', function (Blueprint $table) {
            $table->id();
            $table->decimal('lat_rounded', 4, 1);
            $table->decimal('lon_rounded', 4, 1);
            $table->date('date');
            $table->string('location_name')->nullable();
            $table->string('country', 2)->nullable();
            $table->json('weather_data');
            $table->timestamps();
            
            $table->unique(['lat_rounded', 'lon_rounded', 'date'], 'unique_location_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_locations');
    }
};
