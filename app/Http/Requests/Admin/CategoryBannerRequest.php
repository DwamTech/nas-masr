<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryBannerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'category_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'exists:categories,id',
            ],
            'banner_type' => [
                $isUpdate ? 'sometimes' : 'required',
                'in:home_page,ad_creation',
            ],
            'banner_image' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'image',
                'max:4096', // 4MB
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'display_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'القسم',
            'banner_type' => 'نوع البانر',
            'banner_image' => 'صورة البانر',
            'is_active' => 'مفعل',
            'display_order' => 'ترتيب العرض',
        ];
    }
}
