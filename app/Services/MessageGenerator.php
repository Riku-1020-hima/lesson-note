<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 保護者向けの連絡文を作る。
 * ANTHROPIC_API_KEY があれば Claude で作成し、なければテンプレートで作る。
 */
class MessageGenerator
{
    public const TYPES = [
        'unpaid' => '月謝のお支払いのお願い',
        'makeup' => '振替レッスンのご案内',
        'free' => '自由入力(お知らせなど)',
    ];

    /** @return array{text: string, source: string} */
    public function generate(string $type, Student $student, string $schoolName, string $extra = '', array $context = []): array
    {
        $template = $this->template($type, $student, $schoolName, $extra, $context);

        $apiKey = config('services.anthropic.key');
        if (! $apiKey) {
            return ['text' => $template, 'source' => 'template'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 800,
                'system' => 'あなたは習い事教室の事務を手伝うアシスタントです。保護者に送るLINEやメールの文面を、丁寧で温かく、簡潔な日本語で書きます。'
                    .'事実として渡された情報以外(金額・日付・名前)は作らないでください。本文のみを出力し、件名や前置きは書かないでください。',
                'messages' => [[
                    'role' => 'user',
                    'content' => "次の下書きを、相手が気持ちよく読める文面に整えてください。\n"
                        ."用件: ".self::TYPES[$type]."\n"
                        ."追加の指示: ".($extra ?: 'なし')."\n\n"
                        ."下書き:\n".$template,
                ]],
            ]);

            $text = trim((string) data_get($response->json(), 'content.0.text'));
            if ($response->successful() && $text !== '') {
                return ['text' => $text, 'source' => 'ai'];
            }

            Log::warning('Claude API returned no text', ['status' => $response->status()]);
        } catch (Throwable $e) {
            Log::warning('Claude API failed: '.$e->getMessage());
        }

        return ['text' => $template, 'source' => 'template'];
    }

    private function template(string $type, Student $student, string $schoolName, string $extra, array $context): string
    {
        $guardian = ($student->guardian_name ?: $student->name.'さんの保護者').'様';
        $sign = "\n\n{$schoolName}";

        return match ($type) {
            'unpaid' => sprintf(
                "%s\n\nいつも%sをご利用いただき、ありがとうございます。\n%sさんの%s分の月謝(%s円)のお支払いが、まだ確認できておりません。\nお手数ですが、ご確認のうえお支払いをお願いいたします。\n行き違いでお支払い済みの場合は、失礼をお許しください。%s",
                $guardian,
                $schoolName,
                $student->name,
                $context['month_label'] ?? '今月',
                number_format($context['amount'] ?? $student->fee),
                $sign,
            ),
            'makeup' => sprintf(
                "%s\n\nいつもありがとうございます。\n%sさんの振替レッスンについてご案内いたします。\n%s\nご都合が悪くなった場合は、お気軽にご連絡ください。%s",
                $guardian,
                $student->name,
                $context['makeup_label'] ?? '振替日はあらためてご相談させてください。',
                $sign,
            ),
            default => sprintf(
                "%s\n\nいつもありがとうございます。\n%s%s",
                $guardian,
                $extra ?: '(ここにお知らせ内容を入力してください)',
                $sign,
            ),
        };
    }
}
