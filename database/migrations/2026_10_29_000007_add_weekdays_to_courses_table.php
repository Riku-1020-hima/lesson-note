<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1つのコースで複数の曜日(週2回など)を持てるようにする。
 * weekday は「最初の曜日」として並び替え用に残す。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('weekdays')->nullable()->after('weekday');
        });

        // 今あるコースは、今の曜日1つだけを持つ形にそろえる
        DB::table('courses')->orderBy('id')->each(function ($course) {
            DB::table('courses')->where('id', $course->id)->update([
                'weekdays' => json_encode([(int) $course->weekday]),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('weekdays');
        });
    }
};
