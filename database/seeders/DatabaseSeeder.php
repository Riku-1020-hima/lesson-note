<?php

namespace Database\Seeders;

use App\Models\MakeupLesson;
use App\Models\TuitionPayment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * デモ用データ。ログイン: demo@example.com / password
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'さくらピアノ教室',
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $courses = collect([
            ['name' => 'キッズ入門', 'weekdays' => [1], 'start_time' => '16:00', 'end_time' => '16:45', 'capacity' => 6, 'monthly_fee' => 6500],
            ['name' => '小学生クラス', 'weekdays' => [3], 'start_time' => '17:00', 'end_time' => '18:00', 'capacity' => 6, 'monthly_fee' => 7500],
            ['name' => '中高生クラス', 'weekdays' => [5], 'start_time' => '18:30', 'end_time' => '19:30', 'capacity' => 5, 'monthly_fee' => 8500],
            ['name' => '土曜ジュニア', 'weekdays' => [6], 'start_time' => '10:00', 'end_time' => '11:00', 'capacity' => 8, 'monthly_fee' => 7500],
        ])->map(fn ($c) => $user->courses()->create($c));

        $names = [
            ['佐藤 ひなた', 'さとう ひなた', '佐藤 美咲'], ['鈴木 そうま', 'すずき そうま', '鈴木 恵'],
            ['高橋 ゆい', 'たかはし ゆい', '高橋 直子'], ['田中 れん', 'たなか れん', '田中 和也'],
            ['伊藤 さくら', 'いとう さくら', '伊藤 由美'], ['渡辺 はると', 'わたなべ はると', '渡辺 裕子'],
            ['山本 あおい', 'やまもと あおい', '山本 千尋'], ['中村 そら', 'なかむら そら', '中村 健'],
            ['小林 めい', 'こばやし めい', '小林 綾'], ['加藤 いつき', 'かとう いつき', '加藤 真理'],
            ['吉田 ことは', 'よしだ ことは', '吉田 香織'], ['山田 りく', 'やまだ りく', '山田 剛'],
            ['佐々木 ゆな', 'ささき ゆな', '佐々木 智子'], ['松本 かい', 'まつもと かい', '松本 亜希'],
            ['井上 みお', 'いのうえ みお', '井上 純子'], ['木村 ゆうと', 'きむら ゆうと', '木村 浩二'],
            ['林 つむぎ', 'はやし つむぎ', '林 理恵'], ['清水 あさひ', 'しみず あさひ', '清水 舞'],
        ];

        $students = collect($names)->map(function ($n, $i) use ($user, $courses) {
            return $user->students()->create([
                'name' => $n[0],
                'kana' => $n[1],
                'guardian_name' => $n[2],
                'guardian_email' => 'parent'.($i + 1).'@example.com',
                'course_id' => $courses[$i % 4]->id,
                'enrolled_on' => now()->subMonths(3 + $i)->startOfMonth(),
                'status' => $i === 17 ? 'paused' : 'active',
            ]);
        });

        // 先月はほぼ全員入金済み、今月は半分くらい入金済み
        $lastMonth = now()->subMonthNoOverflow()->format('Y-m');
        $thisMonth = now()->format('Y-m');
        foreach ($students->where('status', 'active') as $i => $s) {
            TuitionPayment::create([
                'student_id' => $s->id, 'year_month' => $lastMonth, 'amount' => $s->fee,
                'paid_at' => $i === 3 ? null : now()->subMonthNoOverflow()->startOfMonth()->addDays(4),
                'method' => $i === 3 ? null : '振込',
            ]);
            TuitionPayment::create([
                'student_id' => $s->id, 'year_month' => $thisMonth, 'amount' => $s->fee,
                'paid_at' => $i % 2 === 0 ? now()->startOfMonth()->addDays(3) : null,
                'method' => $i % 2 === 0 ? '振込' : null,
            ]);
        }

        MakeupLesson::create(['student_id' => $students[1]->id, 'absent_date' => now()->subDays(3), 'reason' => '体調不良', 'status' => 'pending']);
        MakeupLesson::create(['student_id' => $students[6]->id, 'absent_date' => now()->subDays(6), 'reason' => '学校行事', 'status' => 'pending']);

        $saturday = now()->next(6);
        MakeupLesson::create([
            'student_id' => $students[2]->id, 'absent_date' => now()->subDays(8), 'reason' => '家族旅行',
            'makeup_course_id' => $courses[3]->id, 'makeup_date' => $saturday, 'status' => 'booked',
        ]);
    }
}
