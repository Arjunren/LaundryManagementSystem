<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LaundryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['customer_id' => ['nullable', 'exists:users,id'], 'service_type_id' => ['required', 'exists:service_types,id'], 'quantity' => ['required', 'numeric', 'min:0.1', 'max:10000'], 'notes' => ['nullable', 'string', 'max:1000']];
    }
}
