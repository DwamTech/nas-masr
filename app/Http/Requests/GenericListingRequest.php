<?php

namespace App\Http\Requests;

use App\Support\Section;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class GenericListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $section = $this->resolveSection();

        // قواعد الإنشاء (تجي من السكشن وفيها required)
        $rules = $section->rules();

        // قواعد إضافية عامة
        $extra = [
            // خليه يتماشى مع حالتك الفعلية بدل draft/published/archived
            'status'       => ['nullable', Rule::in(['Valid', 'Pending', 'Rejected', 'Expired'])],
            'published_at' => ['nullable', 'date'],
        ];

        if ($section->supportsMakeModel()) {
            $extra['make_id'] = ['nullable', 'integer', 'exists:makes,id'];
            $extra['model_id'] = ['nullable', 'integer', 'exists:models,id'];
            $extra['make']    = ['nullable', 'string'];
            $extra['model']   = ['nullable', 'string'];
        }

        $rules = $rules + $extra;

        // --- وضع التعديل: حوّل كل required -> sometimes
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach ($rules as $key => $ruleSet) {
                $rules[$key] = $this->relaxRuleSet($ruleSet);
            }

            // صور/ملفات: ضيف sometimes|nullable
            foreach (['main_image', 'images', 'images.*'] as $fileKey) {
                if (isset($rules[$fileKey])) {
                    $rules[$fileKey] = $this->ensureSometimesNullable($rules[$fileKey]);
                }
            }

            // plan_type في التعديل اختياري
            $rules['plan_type'] = ['sometimes', 'string', 'in:standard,premium,featured,free'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['images']) && !is_array($data['images'])) {
            $data['images'] = [$data['images']];
        }

        if (isset($data['governorate']) && is_string($data['governorate'])) {
            $data['governorate'] = trim($data['governorate']);
        }
        if (isset($data['city']) && is_string($data['city'])) {
            $data['city'] = trim($data['city']);
        }

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach (['negotiable'] as $boolKey) {
                if (array_key_exists($boolKey, $data['attributes'])) {
                    $val = $data['attributes'][$boolKey];
                    $data['attributes'][$boolKey] = filter_var(
                        $val,
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    );
                }
            }
        }

        if (isset($data['attributes']['kilometers']) && is_string($data['attributes']['kilometers'])) {
            $data['attributes']['kilometers'] = str_replace(',', '،', $data['attributes']['kilometers']);
        }

        if (isset($data['plan_type']) && is_string($data['plan_type'])) {
            $data['plan_type'] = trim($data['plan_type']);
        }

        $this->replace($data);
    }

    public function attributes(): array
    {
        $attrs = [
            'title'            => 'العنوان',
            'price'            => 'السعر',
            'description'      => 'الوصف',
            'governorate_id'   => 'المحافظة',
            'city_id'          => 'المدينة',
            'governorate'      => 'المحافظة',
            'city'             => 'المدينة',
            'lat'              => 'خط العرض',
            'lng'              => 'خط الطول',
            'address'          => 'العنوان',
            'main_image'       => 'الصورة الرئيسية',
            'images'           => 'معرض الصور',
            'images.*'         => 'الصورة',
            'status'           => 'الحالة',
            'published_at'     => 'تاريخ النشر',
            'plan_type'        => 'نوع الخطة',
            'contact_phone'    => 'رقم الاتصال',
            'whatsapp_phone'   => 'رقم الواتساب',
            'make_id'          => 'الماركة',
            'model_id'         => 'الموديل',
            'make'             => 'الماركة',
            'model'            => 'الموديل',
        ];

        try {
            $section = $this->resolveSection();
            foreach ($section->fields as $f) {
                $attrs['attributes.' . $f['field_name']] = $f['display_name'] ?? $f['field_name'];
            }
        } catch (\Throwable $e) {
        }

        return $attrs;
    }

    public function messages(): array
    {
        $msgs = [
            'required' => 'حقل :attribute مطلوب.',
            'integer'  => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'numeric'  => 'حقل :attribute يجب أن يكون رقمًا.',
            'boolean'  => 'حقل :attribute يجب أن يكون نعم/لا.',
            'date'     => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'in'       => 'قيمة :attribute غير ضمن القيم المسموح بها.',
            'max'      => 'طول :attribute أكبر من المسموح.',
            'exists'   => ':attribute غير موجود.',
            'array'    => 'حقل :attribute يجب أن يكون قائمة.',
            'distinct' => 'حقل :attribute يحتوي عناصر مكرّرة.',
        ];

        try {
            $section = $this->resolveSection();
            foreach ($section->fields as $f) {
                if (!empty($f['options'])) {
                    $key = 'attributes.' . $f['field_name'] . '.in';

                    $opts = is_array($f['options'])
                        ? $f['options']
                        : json_decode($f['options'], true);

                    if (is_array($opts) && $opts) {
                        $allowed = implode('، ', $opts);
                        $label = $f['display_name'] ?? $f['field_name'];

                        $msgs[$key] = "قيمة {$label} غير ضمن القيم المسموح بها. القيم المتاحة: {$allowed}.";
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return $msgs;
    }

    protected function resolveSection(): Section
    {
        $param = $this->route('section'); 
        if ($param instanceof Section) {
            return $param;
        }
        return Section::fromSlug((string) $param);
    }

    // ======= Helpers =======


    protected function relaxRuleSet($ruleSet): array
    {
        if (is_string($ruleSet)) {
            $ruleSet = explode('|', $ruleSet);
        }

        $ruleSet = array_values(array_filter($ruleSet, function ($r) {
            $r = (string)$r;
            if ($r === 'required') return false;
            if (str_starts_with($r, 'required_if')) return false;
            if (str_starts_with($r, 'required_unless')) return false;
            return true;
        }));

        array_unshift($ruleSet, 'sometimes');

        return $ruleSet;
    }

    
    protected function ensureSometimesNullable($ruleSet): array
    {
        if (is_string($ruleSet)) {
            $ruleSet = explode('|', $ruleSet);
        }

        if (!in_array('sometimes', $ruleSet, true)) {
            array_unshift($ruleSet, 'sometimes');
        }
        if (!in_array('nullable', $ruleSet, true)) {
            array_unshift($ruleSet, 'nullable');
        }

        $ruleSet = array_values(array_filter($ruleSet, fn($r) => $r !== 'required'));

        return $ruleSet;
    }
}
