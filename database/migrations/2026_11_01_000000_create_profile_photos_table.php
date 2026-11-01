<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photos live in the database because the container disk on Render is wiped on every restart/redeploy.
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('mime', 50);
            $table->mediumText('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_photos');
    }
};
