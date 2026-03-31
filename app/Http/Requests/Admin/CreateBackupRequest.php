<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by auth:sanctum + dashboard.access + admin middleware stack.
        // No need to duplicate the check here.
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:full,db,files'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'نوع النسخة الاحتياطية يجب أن يكون: full أو db أو files.',
        ];
    }
}
