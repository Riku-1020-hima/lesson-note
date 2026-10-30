<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('makeup_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('absent_date');
            $table->string('reason')->nullable();
            $table->foreignId('makeup_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->date('makeup_date')->nullable();
            $table->string('status')->default('pending'); // pending / booked / done / cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('makeup_lessons');
    }
};
