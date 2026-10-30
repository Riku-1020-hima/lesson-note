<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\MakeupLesson;
use App\Models\Student;
use App\Models\TuitionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LessonNoteTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create(['name' => 'テスト教室', 'email' => 'o'.uniqid().'@example.com', 'password' => 'password']);
    }

    private function course(User $u, array $attrs = []): Course
    {
        return $u->courses()->create($attrs + [
            'name' => '月曜クラス', 'weekdays' => [1], 'start_time' => '16:00', 'end_time' => '17:00',
            'capacity' => 2, 'monthly_fee' => 7000,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('レッスンノート');
    }

    public function test_register_and_see_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'さくら教室', 'email' => 'new@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->get('/')->assertOk()->assertSee('さくら教室');
    }

    public function test_all_pages_render(): void
    {
        $u = $this->owner();
        $c = $this->course($u);
        $s = $u->students()->create(['name' => '山田 花子', 'course_id' => $c->id, 'status' => 'active']);
        $m = MakeupLesson::create(['student_id' => $s->id, 'absent_date' => now(), 'status' => 'pending']);

        $this->actingAs($u);
        foreach (['/', '/students', '/students/create', "/students/{$s->id}", "/students/{$s->id}/edit",
                  '/courses', '/courses/create', "/courses/{$c->id}/edit", '/payments', '/payments?month=2026-01',
                  '/makeups', '/makeups/create', "/makeups/{$m->id}/edit", '/messages'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_status_can_be_changed_from_list(): void
    {
        $u = $this->owner();
        $s = $u->students()->create(['name' => 'A', 'status' => 'active']);

        $this->actingAs($u)->from('/students')
            ->patch("/students/{$s->id}/status", ['status' => 'paused'])
            ->assertRedirect('/students');
        $this->assertSame('paused', $s->fresh()->status);

        $this->patch("/students/{$s->id}/status", ['status' => 'unknown'])->assertSessionHasErrors('status');

        $other = $this->owner();
        $this->actingAs($other)->patch("/students/{$s->id}/status", ['status' => 'withdrawn'])->assertNotFound();
    }

    public function test_student_filters_are_remembered_and_can_be_reset(): void
    {
        $u = $this->owner();
        $u->students()->create(['name' => '在籍の子', 'status' => 'active']);
        $u->students()->create(['name' => '休会の子', 'status' => 'paused']);
        $this->actingAs($u);

        $this->get('/students?status=paused')->assertSee('休会の子')->assertDontSee('在籍の子')->assertSee('リセット');

        // 別ページに行って戻っても絞り込みが残る
        $this->get('/payments');
        $this->get('/students')->assertSee('休会の子')->assertDontSee('在籍の子');

        // リセット
        $this->get('/students?reset=1')->assertRedirect('/students');
        $this->get('/students')->assertSee('在籍の子')->assertDontSee('休会の子');
    }

    public function test_payment_month_is_remembered(): void
    {
        $u = $this->owner();
        $this->actingAs($u)->get('/payments?month=2026-03')->assertSee('2026年3月');
        $this->get('/students');
        $this->get('/payments')->assertSee('2026年3月');
    }

    public function test_settings_change_names_and_password(): void
    {
        $u = $this->owner();
        $this->actingAs($u)->get('/settings')->assertOk()->assertSee('レッスンノート');

        $this->put('/settings', ['name' => 'ひまわりダンス', 'app_name' => 'ダンス帳', 'email' => $u->email])
            ->assertRedirect('/settings');
        $this->get('/')->assertSee('ダンス帳')->assertSee('ひまわりダンス')->assertDontSee('レッスンノート');

        // 空欄に戻すと標準の名前
        $this->put('/settings', ['name' => 'ひまわりダンス', 'app_name' => '', 'email' => $u->email]);
        $this->get('/')->assertSee('レッスンノート');

        // 現在のパスワードが違うと変えられない
        $this->put('/settings', ['name' => 'x', 'email' => $u->email, 'current_password' => 'wrong',
            'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertSessionHasErrors('current_password');

        $this->put('/settings', ['name' => 'x', 'email' => $u->email, 'current_password' => 'password',
            'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpass123', $u->fresh()->password));
    }

    public function test_logo_image_can_be_uploaded_and_removed(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $u = $this->owner();
        $this->actingAs($u);

        $this->put('/settings', [
            'name' => $u->name, 'email' => $u->email,
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertSessionHasNoErrors();

        $path = $u->fresh()->logo_path;
        $this->assertNotNull($path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($path);
        $this->get('/')->assertSee('storage/'.$path);

        // 画像以外は受け付けない
        $this->put('/settings', [
            'name' => $u->name, 'email' => $u->email,
            'logo' => \Illuminate\Http\UploadedFile::fake()->create('memo.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('logo');

        // 外すと削除される
        $this->put('/settings', ['name' => $u->name, 'email' => $u->email, 'remove_logo' => '1']);
        $this->assertNull($u->fresh()->logo_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($path);
    }

    public function test_fee_box_shows_course_fee_and_same_amount_follows_course(): void
    {
        $u = $this->owner();
        $c = $this->course($u); // 7000円
        $this->actingAs($u);

        // コースと同じ金額 → コースに合わせる(null)
        $this->post('/students', ['name' => 'A', 'course_id' => $c->id, 'status' => 'active', 'monthly_fee' => 7000]);
        $a = Student::where('name', 'A')->first();
        $this->assertNull($a->monthly_fee);
        $this->get("/students/{$a->id}/edit")->assertSee('value="7000"', false)->assertSee('data-fee="7000"', false);

        // 割引の金額 → 個別設定として残る
        $this->post('/students', ['name' => 'B', 'course_id' => $c->id, 'status' => 'active', 'monthly_fee' => 6000]);
        $this->assertSame(6000, Student::where('name', 'B')->first()->monthly_fee);

        // コースの月謝を上げると、A だけ追従する
        $c->update(['monthly_fee' => 8000]);
        $this->assertSame(8000, $a->fresh()->fee);
        $this->assertSame(6000, Student::where('name', 'B')->first()->fee);
    }

    public function test_tour_texts_point_to_real_places_on_screen(): void
    {
        $views = collect(\Illuminate\Support\Facades\File::allFiles(resource_path('views')))
            ->map(fn ($f) => $f->getContents())->implode("\n");

        foreach (config('tour') as $page => $steps) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($page), "ルートがない: {$page}");
            foreach ($steps as $step) {
                $this->assertStringContainsString('data-tour="'.$step['target'].'"', $views, "画面に場所がない: {$page} / {$step['target']}");
                $this->assertNotEmpty($step['title']);
                $this->assertNotEmpty($step['text']);
            }
        }
    }

    public function test_tour_appears_on_pages(): void
    {
        $u = $this->owner();
        $this->actingAs($u)->get('/')->assertSee('tour-pop')->assertSee('使い方')->assertSee('在籍中の生徒', false);
        $this->get('/students/create')->assertSee('tour-pop');
        // ガイドのないページでは出さない
        $s = $u->students()->create(['name' => 'A', 'status' => 'active']);
        $this->get("/students/{$s->id}")->assertDontSee('tour-pop');
    }

    public function test_setup_checklist_guides_new_classroom(): void
    {
        $u = $this->owner();
        $this->actingAs($u);

        // まっさらな状態:チェックリストと「先にコース」の案内
        $this->get('/')->assertSee('はじめにやること')->assertSee('0 / 4 完了')->assertSee('コースを登録すると、今日の授業がここに出ます');
        $this->get('/students')->assertSee('先にコースを追加する');
        $this->get('/courses')->assertSee('コースを追加する');

        // コースと生徒を登録して入金を記録すると、チェックリストは消える
        $c = $this->course($u);
        $this->get('/')->assertSee('1 / 4 完了');
        $this->get('/students')->assertSee('1人目を登録してみましょう');
        $s = $u->students()->create(['name' => 'A', 'course_id' => $c->id, 'status' => 'active']);
        TuitionPayment::create(['student_id' => $s->id, 'year_month' => now()->format('Y-m'), 'amount' => 7000, 'paid_at' => now()]);
        $this->get('/')->assertDontSee('はじめにやること');
    }

    public function test_payments_are_not_created_before_enrollment_or_far_future(): void
    {
        $u = $this->owner();
        $c = $this->course($u);
        $u->students()->create(['name' => '新しい子', 'course_id' => $c->id, 'status' => 'active', 'enrolled_on' => now()->startOfMonth()]);
        $this->actingAs($u);

        $prev = now()->startOfMonth()->subMonthNoOverflow()->format('Y-m');
        $this->get("/payments?month={$prev}")->assertDontSee('新しい子');
        $this->assertSame(0, TuitionPayment::count());

        $this->get('/payments?month='.now()->format('Y-m'))->assertSee('新しい子');
        $this->assertSame(1, TuitionPayment::count());

        // 来月分までは作る、それより先は作らない
        $this->get('/payments?month='.now()->startOfMonth()->addMonthNoOverflow()->format('Y-m'));
        $this->get('/payments?month='.now()->startOfMonth()->addMonthsNoOverflow(3)->format('Y-m'));
        $this->assertSame(2, TuitionPayment::count());
    }

    public function test_old_unpaid_rows_before_enrollment_are_hidden_but_paid_ones_stay(): void
    {
        $u = $this->owner();
        $s = $u->students()->create(['name' => 'B', 'status' => 'active', 'enrolled_on' => '2026-06-10']);
        TuitionPayment::create(['student_id' => $s->id, 'year_month' => '2026-03', 'amount' => 7000]);
        TuitionPayment::create(['student_id' => $s->id, 'year_month' => '2026-04', 'amount' => 7000, 'paid_at' => now()]);
        $this->actingAs($u);

        $this->get('/payments?month=2026-03')->assertDontSee('7,000円');
        $this->get('/payments?month=2026-04')->assertSee('7,000円');
        $this->get("/students/{$s->id}")->assertDontSee('2026年03月')->assertSee('2026年04月');
    }

    public function test_absent_student_frees_a_seat_for_makeups(): void
    {
        $u = $this->owner();
        $monday = $this->course($u, ['capacity' => 2]);
        $sat = $this->course($u, ['name' => '土曜', 'weekdays' => [6], 'capacity' => 5]);
        $a = $u->students()->create(['name' => 'A', 'course_id' => $monday->id, 'status' => 'active']);
        $u->students()->create(['name' => 'B', 'course_id' => $monday->id, 'status' => 'active']);
        $c = $u->students()->create(['name' => 'C', 'course_id' => $sat->id, 'status' => 'active']);
        $next = now()->next(1);
        $this->actingAs($u);

        $this->assertFalse($monday->hasSeatOn($next)); // 満員

        // A が欠席 → 1席空く
        MakeupLesson::create(['student_id' => $a->id, 'absent_date' => $next, 'status' => 'pending']);
        $this->assertSame(1, $monday->attendeeCountOn($next));
        $this->assertTrue($monday->hasSeatOn($next));

        // C がその席に振替予約できる
        $m = MakeupLesson::create(['student_id' => $c->id, 'absent_date' => now(), 'status' => 'pending']);
        $this->put("/makeups/{$m->id}", ['makeup_course_id' => $monday->id, 'makeup_date' => $next->toDateString()])
            ->assertRedirect('/makeups');
        $this->assertFalse($monday->hasSeatOn($next)); // また満員

        // A の欠席を取り消すと、席は戻らない(数えなくなる)
        MakeupLesson::where('student_id', $a->id)->update(['status' => 'cancelled']);
        $this->assertSame(3, $monday->attendeeCountOn($next));
    }

    public function test_password_can_be_reset_by_email(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $u = $this->owner();

        $this->get('/login')->assertSee('パスワードを忘れた方');
        $this->get('/forgot-password')->assertOk();

        $this->post('/forgot-password', ['email' => $u->email])->assertSessionHas('status');

        $token = null;
        \Illuminate\Support\Facades\Notification::assertSentTo($u, \App\Notifications\ResetPasswordNotification::class,
            function ($n) use (&$token, $u) {
                $token = $n->token;
                $mail = $n->toMail($u);

                return str_contains($mail->subject, 'パスワード再設定') && str_contains($mail->actionUrl, '/reset-password/');
            });

        $this->get("/reset-password/{$token}?email={$u->email}")->assertOk()->assertSee($u->email);

        // 間違ったトークンでは変えられない
        $this->post('/reset-password', ['token' => 'wrong', 'email' => $u->email, 'password' => 'brandnew123', 'password_confirmation' => 'brandnew123'])
            ->assertSessionHasErrors('email');

        $this->post('/reset-password', ['token' => $token, 'email' => $u->email, 'password' => 'brandnew123', 'password_confirmation' => 'brandnew123'])
            ->assertRedirect('/login');

        $this->post('/login', ['email' => $u->email, 'password' => 'brandnew123'])->assertRedirect('/');
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_validation_messages_are_japanese(): void
    {
        app()->setLocale('ja');
        $this->actingAs($this->owner())->post('/students', ['status' => 'active'])
            ->assertSessionHasErrors(['name' => '名前を入力してください。']);
    }

    public function test_payments_can_be_exported_as_csv(): void
    {
        $u = $this->owner();
        $c = $this->course($u);
        $s = $u->students()->create(['name' => '山田 花子', 'kana' => 'やまだ はなこ', 'course_id' => $c->id, 'status' => 'active', 'enrolled_on' => '2026-01-01']);
        TuitionPayment::create(['student_id' => $s->id, 'year_month' => '2026-09', 'amount' => 7000, 'paid_at' => '2026-09-05 10:00:00', 'method' => '振込']);
        TuitionPayment::create(['student_id' => $s->id, 'year_month' => '2026-10', 'amount' => 7000]);

        // 他の教室の記録は出ない
        $other = $this->owner();
        $o = $other->students()->create(['name' => 'よその子', 'status' => 'active']);
        TuitionPayment::create(['student_id' => $o->id, 'year_month' => '2026-10', 'amount' => 9999]);

        $this->actingAs($u);
        $res = $this->get('/payments/export?month=2026-10')->assertOk();
        $csv = $res->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('年月,生徒名,ふりがな,コース,金額,状態,入金日,支払い方法', $csv);
        $this->assertStringContainsString('2026-10,"山田 花子","やまだ はなこ",月曜クラス,7000,未納', $csv);
        $this->assertStringNotContainsString('2026-09', $csv);
        $this->assertStringNotContainsString('よその子', $csv);
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));

        $year = $this->get('/payments/export?year=2026')->streamedContent();
        $this->assertStringContainsString('2026-09,"山田 花子","やまだ はなこ",月曜クラス,7000,入金済,2026-09-05,振込', $year);
        $this->assertStringContainsString('2026-10,', $year);

        $this->get('/payments')->assertSee('CSVで書き出す');
    }

    public function test_makeup_deadline_warns_and_limits_booking(): void
    {
        $u = $this->owner();
        $sat = $this->course($u, ['name' => '土曜', 'weekdays' => [6], 'capacity' => 5]);
        $s = $u->students()->create(['name' => '振替の子', 'status' => 'active']);
        $this->actingAs($u);

        // 期限なし:バッジは出ない
        $m = MakeupLesson::create(['student_id' => $s->id, 'absent_date' => now()->subDays(55), 'status' => 'pending']);
        $this->get('/makeups')->assertDontSee('期限');

        // 設定で60日にすると、残り5日 →「あと5日」
        $this->put('/settings', ['name' => $u->name, 'email' => $u->email, 'makeup_deadline_days' => 60])->assertSessionHasNoErrors();
        $this->assertSame(60, $u->fresh()->makeup_deadline_days);
        $this->get('/makeups')->assertSee('あと5日');
        $this->get('/')->assertSee('うち期限が近い・切れた 1件');

        // 期限より後の日は予約できない
        $farSaturday = now()->addDays(6)->next(6);
        $this->put("/makeups/{$m->id}", ['makeup_course_id' => $sat->id, 'makeup_date' => $farSaturday->toDateString()])
            ->assertSessionHasErrors('makeup_date');

        // 期限切れ
        $m->update(['absent_date' => now()->subDays(61)]);
        $this->get('/makeups')->assertSee('期限切れ');
        $this->get("/makeups/{$m->id}/edit")->assertSee('期限を過ぎているため');

        // 予約済み・完了になったものには期限を出さない
        $m->update(['status' => 'done']);
        $this->get('/makeups?status=all')->assertDontSee('期限切れ');
    }

    public function test_tables_have_phone_layout_labels(): void
    {
        $u = $this->owner();
        $c = $this->course($u);
        $u->students()->create(['name' => 'A', 'course_id' => $c->id, 'status' => 'active']);
        $this->actingAs($u);

        foreach (['/students', '/courses', '/payments', '/makeups'] as $url) {
            $this->get($url)->assertSee('rtable', false);
        }
        $this->get('/payments')->assertSee('data-label="金額"', false);
    }

    public function test_student_fee_falls_back_to_course_fee(): void
    {
        $u = $this->owner();
        $c = $this->course($u);

        $this->actingAs($u)->post('/students', ['name' => 'A', 'course_id' => $c->id, 'status' => 'active', 'monthly_fee' => ''])
            ->assertRedirect('/students');
        $this->assertSame(7000, Student::first()->fee);
    }

    public function test_payments_are_created_for_month_and_can_be_toggled(): void
    {
        $u = $this->owner();
        $c = $this->course($u);
        $s = $u->students()->create(['name' => 'A', 'course_id' => $c->id, 'status' => 'active']);
        $u->students()->create(['name' => 'B', 'course_id' => $c->id, 'status' => 'withdrawn']);

        $this->actingAs($u)->get('/payments?month=2026-10')->assertOk()->assertSee('未納');
        $this->assertSame(1, TuitionPayment::count());

        $p = TuitionPayment::first();
        $this->post("/payments/{$p->id}/toggle", ['method' => '振込']);
        $this->assertNotNull($p->fresh()->paid_at);
        $this->assertSame('振込', $p->fresh()->method);

        $this->post("/payments/{$p->id}/toggle");
        $this->assertNull($p->fresh()->paid_at);
    }

    public function test_course_with_several_weekdays_as_one_course(): void
    {
        $u = $this->owner();
        $this->actingAs($u)->post('/courses', [
            'name' => 'キッズ', 'weekdays' => ['4', '1'],
            'start_time' => '16:00', 'end_time' => '16:45', 'capacity' => 6, 'monthly_fee' => 12000,
        ])->assertRedirect('/courses');

        $c = Course::sole();
        $this->assertSame([1, 4], $c->days);        // 月曜はじまりに並ぶ
        $this->assertSame(1, $c->weekday);
        $this->assertSame('月・木曜 16:00〜16:45', $c->schedule_label);
        $this->assertTrue($c->meetsOn(now()->next(4)));
        $this->assertFalse($c->meetsOn(now()->next(3)));
        $this->get('/courses')->assertSee('週2回');

        // 振替は木曜の枠にも入れる
        $s = $u->students()->create(['name' => 'A', 'status' => 'active']);
        $m = MakeupLesson::create(['student_id' => $s->id, 'absent_date' => now(), 'status' => 'pending']);
        $this->put("/makeups/{$m->id}", ['makeup_course_id' => $c->id, 'makeup_date' => now()->next(4)->toDateString()])
            ->assertRedirect('/makeups');
    }

    public function test_course_needs_at_least_one_weekday(): void
    {
        $this->actingAs($this->owner())->post('/courses', [
            'name' => 'X', 'start_time' => '16:00', 'end_time' => '17:00', 'capacity' => 6, 'monthly_fee' => 1000,
        ])->assertSessionHasErrors('weekdays');
    }

    public function test_makeup_booking_respects_capacity_and_weekday(): void
    {
        $u = $this->owner();
        $c = $this->course($u, ['capacity' => 2]);
        $a = $u->students()->create(['name' => 'A', 'course_id' => $c->id, 'status' => 'active']);
        $u->students()->create(['name' => 'B', 'course_id' => $c->id, 'status' => 'active']); // 満員
        $other = $this->course($u, ['name' => '土曜', 'weekdays' => [6], 'capacity' => 5]);

        $m = MakeupLesson::create(['student_id' => $a->id, 'absent_date' => now(), 'status' => 'pending']);
        $this->actingAs($u);

        // 満員のコース
        $monday = now()->next(1)->toDateString();
        $this->put("/makeups/{$m->id}", ['makeup_course_id' => $c->id, 'makeup_date' => $monday])
            ->assertSessionHasErrors('makeup_date');

        // 曜日違い
        $this->put("/makeups/{$m->id}", ['makeup_course_id' => $other->id, 'makeup_date' => $monday])
            ->assertSessionHasErrors('makeup_date');

        // 空きのある土曜
        $saturday = now()->next(6)->toDateString();
        $this->put("/makeups/{$m->id}", ['makeup_course_id' => $other->id, 'makeup_date' => $saturday])
            ->assertRedirect('/makeups');
        $this->assertSame('booked', $m->fresh()->status);
    }

    public function test_users_cannot_see_other_classrooms_data(): void
    {
        $owner = $this->owner();
        $c = $this->course($owner);
        $s = $owner->students()->create(['name' => '秘密', 'course_id' => $c->id, 'status' => 'active']);
        $p = TuitionPayment::create(['student_id' => $s->id, 'year_month' => '2026-10', 'amount' => 7000]);

        $intruder = $this->owner();
        $this->actingAs($intruder);
        $this->get("/students/{$s->id}")->assertNotFound();
        $this->get("/courses/{$c->id}/edit")->assertNotFound();
        $this->post("/payments/{$p->id}/toggle")->assertNotFound();
        $this->get('/students')->assertDontSee('秘密');
        $this->post('/students', ['name' => 'X', 'course_id' => $c->id, 'status' => 'active'])->assertSessionHasErrors('course_id');
    }

    public function test_message_uses_template_without_api_key(): void
    {
        config(['services.anthropic.key' => null]);
        $u = $this->owner();
        $c = $this->course($u);
        $s = $u->students()->create(['name' => '山田 花子', 'guardian_name' => '山田 母', 'course_id' => $c->id, 'status' => 'active']);
        TuitionPayment::create(['student_id' => $s->id, 'year_month' => '2026-10', 'amount' => 7000]);

        $this->actingAs($u)->post('/messages', ['type' => 'unpaid', 'student_id' => $s->id])
            ->assertOk()
            ->assertSee('山田 母様')
            ->assertSee('7,000円')
            ->assertSee('10月')
            ->assertSee('テンプレート');
    }

    public function test_message_uses_claude_when_key_is_set(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'AIが整えた文面です']]]),
        ]);

        $u = $this->owner();
        $s = $u->students()->create(['name' => 'A', 'status' => 'active']);

        $this->actingAs($u)->post('/messages', ['type' => 'free', 'student_id' => $s->id, 'extra' => '発表会のお知らせ'])
            ->assertOk()->assertSee('AIが整えた文面です')->assertSee('AIで作成');

        Http::assertSent(fn ($req) => $req->hasHeader('x-api-key', 'test-key')
            && str_contains($req['messages'][0]['content'], '発表会のお知らせ'));
    }

    public function test_message_falls_back_when_api_fails(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'x'], 500)]);

        $u = $this->owner();
        $s = $u->students()->create(['name' => 'A', 'status' => 'active']);

        $this->actingAs($u)->post('/messages', ['type' => 'free', 'student_id' => $s->id, 'extra' => 'テスト'])
            ->assertOk()->assertSee('テンプレート');
    }
}
