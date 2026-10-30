<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 振替の期限(欠席日から何日以内か)。空なら期限なし
            $table->unsignedSmallInteger('makeup_deadline_days')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('makeup_deadline_days');
        });
    }
};
