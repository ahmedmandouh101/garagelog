<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyAccountEmail extends VerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        $path = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification()),
        ], absolute: false);

        return rtrim(config('app.url'), '/').$path;
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)->subject('تأكيد بريد حسابك في GarageLog')
            ->greeting('أهلًا بيك')
            ->line('أكد بريدك الإلكتروني علشان تستخدم خدمات GarageLog.')
            ->action('تأكيد البريد الإلكتروني', $url)
            ->line('الرابط صالح لمدة 60 دقيقة. لو معملتش الحساب، تجاهل الرسالة.');
    }
}
