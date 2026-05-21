<?php

namespace App\Mail;

use App\Models\Car;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Car $car) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ' Your Car Service is Due in 7 Days!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.service-reminder',
            with: [
                'car'             => $this->car,
                'owner'           => $this->car->owner,
                'latestService'   => $this->car->serviceRecords()
                                        ->with('garage')
                                        ->latest()
                                        ->first(),
            ],
        );
    }
}
