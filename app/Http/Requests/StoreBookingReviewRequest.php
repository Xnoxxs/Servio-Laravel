<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreBookingReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('review', $this->route('booking'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
