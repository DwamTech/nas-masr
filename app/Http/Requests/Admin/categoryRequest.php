<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // لو عندك صلاحيات أدق، عدّلي الكلام ده
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // لو عندك Route Model Binding هيبقى object، لو ID عادي هيبقى رقم
        $category = $this->route('category');
        $id = is_object($category) ? $category->id : $category;

        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [

            'name' => [
                // ✅ كله اختياري في التعديل
                $isUpdate ? 'sometimes' : 'nullable',
                'string',
                'max:150',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'icon' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'image',
                'max:4096', // 4MB
            ],
            'default_image' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'image',
                'max:4096', // 4MB
            ],
            'is_active' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'boolean',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            // 'slug'      => 'المعرف (slug)',
            // 'name'      => 'الاسم',
            'title'     => 'العنوان',
            'icon'      => 'الأيقونة',
            'is_active' => 'مفعل',
        ];
    }
}
