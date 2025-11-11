<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class categoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         $id = $this->route('category');

        return [
            'slug' => ['required', 'string', 'max:100', 'unique:categories,slug,' . $id],
            'name' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'المعرف (slug)',
            'name' => 'الاسم',
            'icon' => 'الأيقونة',
            'is_active' => 'مفعل',
        ];
    }
    }

