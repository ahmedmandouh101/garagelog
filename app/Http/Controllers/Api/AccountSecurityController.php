<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountSecurityController extends Controller
{
    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        try {
            Password::sendResetLink($data);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json(['message' => 'لو البريد مرتبط بحساب، هتوصلك رسالة استعادة كلمة المرور.']);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255', 'token' => 'required|string|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        $status = DB::transaction(function () use ($data) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->lockForUpdate()->first();

            return Password::reset($data, function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                event(new PasswordReset($user));
            });
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => 'رابط الاستعادة غير صحيح أو انتهت صلاحيته. اطلب رابط جديد.']);
        }

        return response()->json(['message' => 'كلمة المرور اتغيرت، وتم تسجيل الخروج من كل الأجهزة. ادخل بالكلمة الجديدة.']);
    }

    public function sendVerification(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'لو البريد لسه مش متأكد، هتوصلك رسالة التأكيد.']);
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->view('auth.email-verified')->header('Referrer-Policy', 'no-referrer');
    }
}
