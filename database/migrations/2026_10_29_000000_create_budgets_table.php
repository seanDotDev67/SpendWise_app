<?php
// database/migrations/2026_10_29_000000_create_budgets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);              // monthly limit
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);    // one budget per category
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};