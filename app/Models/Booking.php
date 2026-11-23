<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['scheduled_at'])]
class Booking extends Model
{
    public const APPOINTMENT_TIMEZONE = 'Europe/Madrid';

    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => BookingStatus::class,
            'price_cents' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function canTransitionTo(BookingStatus $status): bool
    {
        $beforeStart = $this->scheduled_at->isFuture();

        return match ($status) {
            BookingStatus::Accepted => $this->status === BookingStatus::Pending && $beforeStart,
            BookingStatus::Rejected => $this->status === BookingStatus::Pending,
            BookingStatus::Cancelled => $beforeStart && in_array($this->status, [BookingStatus::Pending, BookingStatus::Accepted], true),
            BookingStatus::Completed => $this->status === BookingStatus::Accepted && ! $beforeStart,
            BookingStatus::Pending => false,
        };
    }

    public function transitionTo(BookingStatus $status): void
    {
        DB::transaction(function () use ($status): void {
            // Serialize all decisions for this service before reading its reservations.
            Service::query()->lockForUpdate()->findOrFail($this->service_id);
            $booking = self::query()->lockForUpdate()->findOrFail($this->id);

            if (! $booking->canTransitionTo($status)) {
                throw ValidationException::withMessages(['booking' => 'This action is no longer available for the booking.']);
            }

            if ($status === BookingStatus::Accepted && $booking->overlapsReservation()) {
                throw ValidationException::withMessages(['booking' => 'An accepted appointment for this service overlaps the requested time.']);
            }

            $booking->status = $status;
            $booking->save();
        }, attempts: 3);
    }

    private function overlapsReservation(): bool
    {
        $appointmentEnd = $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
        $reservations = self::query()
            ->where('service_id', $this->service_id)
            ->whereIn('status', [BookingStatus::Accepted->value, BookingStatus::Completed->value])
            ->where('scheduled_at', '<', $appointmentEnd)
            ->lockForUpdate()
            ->get(['id', 'scheduled_at', 'duration_minutes']);

        return $reservations->contains(
            fn (self $reservation): bool => $reservation->scheduled_at->addMinutes($reservation->duration_minutes)->gt($this->scheduled_at)
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
