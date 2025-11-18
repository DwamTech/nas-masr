<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    ];

    protected function rules(): array
    {
        return [
            'support_number'     => ['nullable','string','max:255'],
            'sub_support_number' => ['nullable','string','max:255'],
            'emergency_number'   => ['nullable','string','max:255'],
            'panner_image'       => ['nullable','string','max:1024'],
            'privacy_policy'     => ['nullable','string'],
            'terms_conditions-main_' => ['nullable','string'],
            'facebook'           => ['nullable','url','max:1024'],
            'twitter'            => ['nullable','url','max:1024'],
            'instagram'          => ['nullable','url','max:1024'],
            'email'              => ['nullable','email','max:255'],
        ];
    }

    protected function typeForKey(string $key): string
    {
        return in_array($key, ['privacy_policy','terms_conditions-main_'], true) ? 'text' : 'string';
    }

    protected function groupForKey(string $key): string
    {
        return $key === 'panner_image' ? 'appearance' : 'general';
    }

    public function index()
    {
        $rows = SystemSetting::whereIn('key', $this->allowedKeys)->get();
        $map = [];
        foreach ($rows as $row) {
            $map[$row->key] = $row->value;
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
            ->filter(fn($v) => $v !== null)
            ->all();

        foreach ($payload as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value'    => (string) $value,
                    'type'     => $this->typeForKey($key),
                    'group'    => $this->groupForKey($key),
                    'autoload' => true,
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }

    public function show(string $id)
    {
        $row = SystemSetting::where('key', $id)->first();
        if (!$row) {
            $row = SystemSetting::find($id);
        }
        if (!$row || !in_array($row->key, $this->allowedKeys, true)) {
            abort(404);
        }
        return response()->json([$row->key => $row->value]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate($this->rules());

        $payload = collect($validated)
            ->only($this->allowedKeys)
            ->filter(fn($v) => $v !== null)
            ->all();

        foreach ($payload as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value'    => (string) $value,
                    'type'     => $this->typeForKey($key),
                    'group'    => $this->groupForKey($key),
                    'autoload' => true,
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }

    public function destroy(string $id)
    {
        $row = SystemSetting::where('key', $id)->first();
        if ($row && in_array($row->key, $this->allowedKeys, true)) {
            $row->delete();
            return response()->json(null, 204);
        }
        abort(404);
    }
}
