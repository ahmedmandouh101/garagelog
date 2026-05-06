<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReviewReceived extends Notification
{
    use Queueable;

    public function __construct(public Review $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message'    => 'You received a new review.',
            'rating'     => $this->review->rating,
            'comment'    => $this->review->comment,
            'owner_name' => $this->review->owner->name,
            'record_id'  => $this->review->service_record_id,
        ];
    }
}
