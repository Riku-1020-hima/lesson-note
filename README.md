# レッスンノート

**習い事教室(ピアノ・ダンス・英会話など)のための、月謝と振替の管理アプリ**

個人経営の教室では、月謝の入金チェックや振替レッスンの調整を、紙やExcel、LINEで管理していることが多くあります。レッスンノートは、こうした作業を1つの画面にまとめて、先生がレッスンに集中できるようにするためのWebアプリです。

## 主な機能

| 機能 | できること |
| --- | --- |
| ホーム | 在籍人数、今月の入金率、未納の合計、振替待ち、今日のクラスをひと目で確認 |
| 生徒管理 | 登録・検索・絞り込み(ページを移っても保持)、在籍/休会/退会を一覧から切り替え |
| コース管理 | 曜日(週2回などの複数曜日にも対応)・時間・定員・月謝を登録 |
| 月謝 | 月ごとの入金チェック表。在籍中の生徒の行は毎月自動で作成(入会前の月は作らない) |
| 振替 | 欠席を登録 → 4週間分の空き枠から予約。定員・曜日・欠席者の空き席を自動で計算 |
| AI連絡文 | 未納のお願いや振替の案内文を作成。金額・日付は登録データから入れ、AI(Claude)は文章を整えるだけ |
| はじめての人向け | 「はじめにやること」チェックリストと、各ページの使い方ガイド |
| 設定 | 教室名・アプリの表示名・アイコン画像・ログイン情報を1ページで変更 |

ほかに、パスワードの再設定(メール)、入力途中のフォームの一時保存、教室ごとのデータ分離にも対応しています。

## 使っている技術

- PHP 8.3 / Laravel 12
- SQLite(MySQL などにも切り替え可能)
- Blade + Tailwind CSS 4(Vite でビルド)
- Claude API(保護者への連絡文。APIキーがなくてもテンプレートで動作)
- PHPUnit によるテスト(画面表示、月謝の自動作成、振替の定員、他の教室のデータが見えないこと、パスワード再設定など)

## 動かし方

```bash
git clone https://github.com/<あなたのユーザー名>/lesson-note.git
cd lesson-note
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # デモデータ入り
php artisan storage:link
npm install && npm run build
php artisan serve
```

http://127.0.0.1:8000 を開き、次のデモアカウントでログインできます。

- メールアドレス:`demo@example.com`
- パスワード:`password`

まっさらな状態から試すときは、`php artisan migrate`(`--seed` なし)のあと「無料で登録」から教室を作ってください。

### AIで連絡文を作る(任意)

`.env` に Claude の APIキーを設定すると、連絡文をAIが読みやすく整えます。

```
ANTHROPIC_API_KEY=sk-ant-...
```

### テスト

```bash
php artisan test
```

## 主なファイル

| 場所 | 中身 |
| --- | --- |
| `app/Models` | Course / Student / TuitionPayment / MakeupLesson / User |
| `app/Services/TuitionService.php` | 月謝の行の自動作成と集計 |
| `app/Services/MessageGenerator.php` | 連絡文の作成(Claude API+テンプレート) |
| `app/Http/Controllers` | 各画面の処理(教室ごとにデータを分離) |
| `config/tour.php` | 使い方ガイドの文章(ここを書き換えると吹き出しの文章が変わります) |
| `lang/ja` | 日本語のエラーメッセージ |
| `tests/Feature/LessonNoteTest.php` | 機能のテスト |

## これから追加したい機能

- スマホで使いやすい画面
- 振替の期限(例:2ヶ月以内)と期限が近いもののお知らせ
- 月謝の記録のCSV書き出し
- 保護者が自分で振替を予約できるページ
- オンラインでの月謝の支払い
