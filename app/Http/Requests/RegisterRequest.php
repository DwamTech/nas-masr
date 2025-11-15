<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        $existingUser = User::where('phone', $this->phone)->first();

        return [
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'max:15',

                !$existingUser ? Rule::unique('users', 'phone') : null,
            ],
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'min:6',
            ],
            'referral_code' => ['nullable', 'string', 'max:20'],
            'lat' => ['nullable', 'decimal:7,4'],
            'lng' => ['nullable', 'decimal:7,4'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The name must be a valid string.',
            'name.max' => 'The name may not be greater than 100 characters.',
            'phone.required' => 'The phone number is required.',
            'phone.unique' => 'This phone number is already taken.',
            'phone.max' => 'The phone number must not exceed 15 digits.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'referral_code.max' => 'Agent number may not be longer than 20 characters.',
            'lat.decimal' => 'Latitude must be a decimal number with 7 digits before the decimal point and 4 digits after the decimal point.',
            'lng.decimal' => 'Longitude must be a decimal number with 7 digits before the decimal point and 4 digits after the decimal point.',
        ];
    }

}
