<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'app_name' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'logo.image' => '画像ファイルを選んでください。',
            'logo.mimes' => 'JPG・PNG・WebP・GIF の画像を選んでください。',
            'logo.max' => '画像は2MBまでにしてください。',
            'current_password.current_password' => '現在のパスワードが違います。',
            'current_password.required_with' => 'パスワードを変えるときは、現在のパスワードも入力してください。',
        ]);

        $user->fill([
            'name' => $data['name'],
            'app_name' => $data['app_name'] ?? null,
            'email' => $data['email'],
        ]);

        // アイコン画像:新しい画像を選んだら差し替え、「画像を外す」なら削除
        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($user->logo_path) {
                Storage::disk('public')->delete($user->logo_path);
            }
            $user->logo_path = $request->hasFile('logo')
                ? $request->file('logo')->store('logos', 'public')
                : null;
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('settings.edit')->with('status', '設定を保存しました。');
    }
}
