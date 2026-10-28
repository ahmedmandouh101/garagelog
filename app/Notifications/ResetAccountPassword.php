<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetAccountPassword extends ResetPassword
{
    protected function resetUrl($notifiable)
    {
        return rtrim(config('app.url'), '/').'/#'.http_build_query([
            'reset_token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset(),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)->subject('استعادة كلمة مرور GarageLog')
            ->greeting('أهلًا بيك')->line('وصلنا طلب لتغيير كلمة مرور حسابك.')
            ->action('اختيار كلمة مرور جديدة', $url)
            ->line('الرابط صالح لمدة '.config('auth.passwords.users.expire').' دقيقة ويُستخدم مرة واحدة.')
            ->line('لو مطلبتش تغيير كلمة المرور، تجاهل الرسالة.');
    }
}
