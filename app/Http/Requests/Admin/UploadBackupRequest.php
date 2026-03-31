<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadBackupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:gz,sql,zip',
                'max:512000', // 500 MB max
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'يجب اختيار ملف النسخة الاحتياطية.',
            'file.file'     => 'الملف المرفوع غير صالح.',
            'file.mimes'    => 'يجب أن يكون الملف من نوع: gz, sql, zip.',
            'file.max'      => 'حجم الملف يجب ألا يتجاوز 500 ميجابايت.',
        ];
    }
}
