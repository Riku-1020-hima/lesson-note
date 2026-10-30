<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuition_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('year_month', 7); // 例: 2026-10
            $table->unsignedInteger('amount');
            $table->dateTime('paid_at')->nullable();
            $table->string('method')->nullable(); // 現金 / 振込 など
            $table->timestamps();

            $table->unique(['student_id', 'year_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_payments');
    }
};
