<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemSetting;

class SystemSettingController extends Controller
{
    protected array $allowedKeys = [
        'support_number',
        'panner_image',
        'privacy_policy',
        'terms_conditions-main_',
        'sub_support_number',
        'emergency_number',
        'facebook',
        'twitter',
        'instagram',
        'email',
        'featured_users_count',
        'show_phone',
        'manual_approval',
        'enable_global_external_notif',
        'free_ads_count',
        'free_ads_max_price'
    ];

    // مفاتيح حسب النوع
    protected array $booleanKeys = ['show_phone','manual_approval','enable_global_external_notif'];
    protected array $integerKeys = ['featured_users_count','free_ads_count','free_ads_max_price'];

    protected function rules(): array
    {
        return [
            'support_number'        => ['nullable', 'string', 'max:255'],
            'sub_support_number'    => ['nullable', 'string', 'max:255'],
            'emergency_number'      => ['nullable', 'string', 'max:255'],
            'panner_image'          => ['nullable', 'string', 'max:1024'],
            'privacy_policy'        => ['nullable', 'string'],
            'terms_conditions-main_' => ['nullable', 'string'],
            'facebook'              => ['nullable', 'url', 'max:1024'],
            'twitter'               => ['nullable', 'url', 'max:1024'],
            'instagram'             => ['nullable', 'url', 'max:1024'],
            'email'                 => ['nullable', 'email', 'max:255'],
            'show_phone'            => ['nullable', 'boolean'],
            'featured_users_count'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'manual_approval'=>['nullable','boolean'],
            'enable_global_external_notif' => ['nullable', 'boolean'],
            'free_ads_count'        => ['nullable', 'integer', 'min:0'],
            'free_ads_max_price'    => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function typeForKey(string $key): string
    {
        if (in_array($key, $this->booleanKeys, true)) return 'boolean';
        if (in_array($key, $this->integerKeys, true)) return 'integer';
        return in_array($key, ['privacy_policy', 'terms_conditions-main_'], true) ? 'text' : 'string';
    }

    protected function groupForKey(string $key): string
    {
        if ($key === 'panner_image') return 'appearance';
        if (in_array($key, ['free_ads_count','free_ads_max_price'], true)) return 'ads';
        return 'general';
    }

    // قبل التخزين: موحّد كل حاجة كـ string (مع تحويل منطقي للأرقام والبوليان)
    protected function castForStorage(string $key, $value): string
    {
        if (in_array($key, $this->booleanKeys, true)) {
            return $value ? '1' : '0';
        }
        if (in_array($key, $this->integerKeys, true)) {
            return (string) (int) $value;
        }
        return (string) $value;
    }

    // قبل الإخراج: Typed فعلي للفرونت
    protected function castForOutput(string $type, ?string $value)
    {
        return match ($type) {
            'boolean' => (bool) ($value === '1' || $value === 1 || $value === true),
            'integer' => $value !== null ? (int) $value : null,
            default   => $value,
        };
    }

    // نسيان الكاش للمفتاح + التجمّع (لو بتستخدمه)
    protected function forgetCache(string $key): void
    {
        Cache::forget("settings:{$key}");
        Cache::forget('settings:autoload'); // لو بتخزن تجميعة autoload
    }

    public function index()
    {
        $rows = SystemSetting::whereIn('key', $this->allowedKeys)->get(['key', 'value', 'type']);
        $map = [];

        foreach ($rows as $row) {
            $type = $row->type ?: $this->typeForKey($row->key);
            $map[$row->key] = $this->castForOutput($type, $row->value);
        }

        foreach ($this->allowedKeys as $k) {
            if (!array_key_exists($k, $map)) {
                $map[$k] = null;
            }
        }

        return response()->json($map);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $payload = collect($validated)
            ->only($this->allowedKeys)
            ->filter(fn ($v) => $v !== null)
            ->all();

        foreach ($payload as $key => $value) {
            $type = $this->typeForKey($key);

            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value'    => $this->castForStorage($key, $value),
                    'type'     => $type,
                    'group'    => $this->groupForKey($key),
                    'autoload' => true,
                ]
            );

            // مهم: امسح الكاش فورًا (بما فيه featured_users_count)
            $this->forgetCache($key);
        }

        return response()->json([
            'status' => 'ok',
            'data'   => $payload,
        ]);
    }

    public function show(string $id)
    {
        $row = SystemSetting::where('key', $id)->first() ?: SystemSetting::find($id);

        if (!$row || !in_array($row->key, $this->allowedKeys, true)) {
            abort(404);
        }

        $type = $row->type ?: $this->typeForKey($row->key);

        return response()->json([
            $row->key => $this->castForOutput($type, $row->value)
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate($this->rules());

        $payload = collect($validated)
            ->only($this->allowedKeys)
            ->filter(fn ($v) => $v !== null)
            ->all();

        foreach ($payload as $key => $value) {
            $type = $this->typeForKey($key);

            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value'    => $this->castForStorage($key, $value),
                    'type'     => $type,
                    'group'    => $this->groupForKey($key),
                    'autoload' => true,
                ]
            );

            // مهم: امسح الكاش فورًا
            $this->forgetCache($key);
        }

        return response()->json(['status' => 'ok']);
    }

    public function destroy(string $id)
    {
        $row = SystemSetting::where('key', $id)->first();

        if ($row && in_array($row->key, $this->allowedKeys, true)) {
            $this->forgetCache($row->key);
            $row->delete();
            return response()->json(null, 204);
        }

        abort(404);
    }
}
