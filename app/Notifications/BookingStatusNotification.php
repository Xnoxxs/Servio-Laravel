<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\BookingStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(
        public int $bookingId,
        public BookingStatus $status,
        public string $serviceTitle,
        public string $appointment,
    ) {}

    public static function dispatchAfterCommit(Booking $booking, BookingStatus $status): void
    {
        DB::afterCommit(function () use ($booking, $status): void {
            try {
                $notification = new self(
                    $booking->id,
                    $status,
                    $booking->service->title,
                    $booking->scheduled_at->copy()->setTimezone(Booking::APPOINTMENT_TIMEZONE)->format('j M Y, H:i T'),
                );

                $recipient = $notification->isForProvider() ? $booking->service->provider : $booking->customer;
                $recipient->notify($notification);
            } catch (Throwable $exception) {
                // Booking writes have already committed, even if queue submission or sync mail fails.
                report($exception);
            }
        });
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $message] = match ($this->status) {
            BookingStatus::Pending => ['New booking request', 'A customer requested an appointment for your service.'],
            BookingStatus::Accepted => ['Booking accepted', 'Your provider accepted your appointment.'],
            BookingStatus::Rejected => ['Booking rejected', 'Your provider declined your request.'],
            BookingStatus::Cancelled => ['Booking cancelled', 'The customer cancelled their appointment.'],
            BookingStatus::Completed => ['Booking completed', 'Your provider marked the appointment as completed.'],
        };

        return (new MailMessage)
            ->subject($subject.' · '.$this->serviceTitle)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($message)
            ->line('Service: '.$this->serviceTitle)
            ->line('Appointment: '.$this->appointment)
            ->action('View booking', route($this->isForProvider() ? 'provider.bookings.show' : 'bookings.show', $this->bookingId));
    }

    private function isForProvider(): bool
    {
        return in_array($this->status, [BookingStatus::Pending, BookingStatus::Cancelled], true);
    }
}
