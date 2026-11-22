<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service
            ? Gate::allows('update', $service)
            : Gate::allows('create', Service::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'regex:/\A[0-9]{1,7}(?:[.,][0-9]{1,2})?\z/'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'location' => ['required', 'string', 'max:255'],
        ];
    }

    public function priceInCents(): int
    {
        [$euros, $cents] = array_pad(explode('.', str_replace(',', '.', (string) $this->validated('price')), 2), 2, '');

        return (int) $euros * 100 + (int) str_pad($cents, 2, '0');
    }
}
