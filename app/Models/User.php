<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const DEFAULT_APP_NAME = 'レッスンノート';

    protected $fillable = ['name', 'app_name', 'logo_path', 'makeup_deadline_days', 'email', 'password'];

    /** アイコン画像のURL(未設定なら null) */
    public function getLogoUrlAttribute(): ?string
    {
        // asset() は今開いているアドレス(lesson-note.test など)を元にURLを作る
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    /** 画面に表示するアプリ名(未設定なら標準の名前) */
    public function getDisplayAppNameAttribute(): string
    {
        return $this->app_name ?: self::DEFAULT_APP_NAME;
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'makeup_deadline_days' => 'integer',
        ];
    }

    /** パスワード再設定メールを日本語で送る */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
