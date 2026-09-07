<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('available'); // available, busy, offline
            $table->geography('location', subtype: 'point', srid: 4326);
            $table->timestamps();

            // (Spatial GiST Index) for fast geographic lookups & KNN searches
            $table->spatialIndex('location');
            // (B-Tree Index) for status filtering
            $table->index('status');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
