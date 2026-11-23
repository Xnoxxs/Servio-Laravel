<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    private CarbonImmutable $appointment;

    public function authorize(): bool
    {
        return Gate::allows('create', [Booking::class, $this->route('service')]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['scheduled_at' => ['required', 'string', 'date_format:Y-m-d\TH:i']];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('scheduled_at')) {
                    return;
                }

                $localTime = $this->string('scheduled_at')->toString();
                $wallClock = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $localTime, 'UTC');
                $timezone = new DateTimeZone(Booking::APPOINTMENT_TIMEZONE);
                $transitions = $timezone->getTransitions($wallClock->subDay()->timestamp, $wallClock->addDay()->timestamp);
                $offsets = array_unique(array_column($transitions, 'offset'));
                $matches = [];

                foreach ($offsets as $offset) {
                    $candidate = $wallClock->subSeconds($offset);

                    if ($candidate->setTimezone($timezone)->format('Y-m-d\TH:i') === $localTime) {
                        $matches[] = $candidate;
                    }
                }

                if (count($matches) !== 1) {
                    $validator->errors()->add('scheduled_at', 'Choose an unambiguous time in Europe/Madrid outside the daylight-saving change.');

                    return;
                }

                if (! $matches[0]->isFuture()) {
                    $validator->errors()->add('scheduled_at', 'Choose a date and time in the future.');

                    return;
                }

                $this->appointment = $matches[0];
            },
        ];
    }

    public function appointment(): CarbonImmutable
    {
        return $this->appointment;
    }
}
