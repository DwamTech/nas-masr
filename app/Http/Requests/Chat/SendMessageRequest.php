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
            'listing_id' => ['nullable', 'integer', 'exists:listings,id'],
            'content_type' => ['nullable', 'string', 'in:text,listing_inquiry,image,video,audio,file'],
            
            // Message validation: required if text or listing, optional if uploading file
            'message' => [
                'nullable', 
                'string', 
                'max:5000', 
                function ($attribute, $value, $fail) {
                    $type = $this->input('content_type', 'text');
                    $hasFile = $this->hasFile('file');
                    
                    if (($type === 'text' || $type === 'listing_inquiry') && empty($value)) {
                        $fail('يجب كتابة رسالة.');
                    }
                    if (!$hasFile && empty($value)) {
                        $fail('يجب كتابة رسالة أو إرفاق ملف.');
                    }
                },
            ],

            // File validation based on content_type
            'file' => [
                'nullable',
                'file',
                function ($attribute, $value, $fail) {
                    $type = $this->input('content_type');
                    // If content_type indicates media, file is required
                    if (in_array($type, ['image', 'video', 'audio', 'file']) && !$value) {
                         $fail('يجب إرفاق ملف لهذا النوع من الرسائل.');
                    }
                },
                // Image validation
                'required_if:content_type,image', 
                'mimes:jpeg,png,jpg,gif,webp,svg', 
                'max:5120', // 5MB

                // Video validation (handled manually or via rule separation? better to use closure or generic mimes if type is mixed, but here we can rely on general checks or strictly content_type)
                // Since 'file' applies to all, we can't easily split max size per type in standard rules without complex conditional logic.
                // Simplified approach: Allow reasonable max for all, check mimes.
                
                // Let's rely on flexible mimes but strict checking
            ],
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
            'file.max' => 'حجم الملف كبير جداً (يجب أن يكون أقل من 5 ميجابايت للصور)',
            'file.mimes' => 'نوع الملف غير مدعوم',
            'file.required_if' => 'يجب اختيار ملف للرفع',
            'content_type.in' => 'نوع الرسالة غير صالح',
        ];
    }
}
