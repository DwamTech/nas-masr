<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by auth middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'receiver_id.required' => 'يجب تحديد المستلم',
            'receiver_id.exists' => 'المستلم غير موجود',
            'message.required' => 'يجب كتابة رسالة',
            'message.min' => 'الرسالة قصيرة جداً',
            'message.max' => 'الرسالة طويلة جداً (الحد الأقصى 5000 حرف)',
        ];
    }
}
