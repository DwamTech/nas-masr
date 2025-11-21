<?php

namespace App\Services;

use App\Models\Listing;
use App\Support\Section;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListingService
{
    public function create(Section $section, array $data, int $userId): Listing
    {
        return DB::transaction(function () use ($section, $data, $userId) {
            $this->normalizeLocationIdsOrFail($data);


            if ($section->supportsMakeModel()) {
                $this->normalizeMakeModel($data);
            } else {
                unset($data['make_id'], $data['model_id'], $data['make'], $data['model']);
            }

            $common = Arr::only($data, [
                'price',
                'description',
                'governorate_id',
                'city_id',
                'lat',
                'lng',
                'address',
                'main_image',
                'images',
                'status',
                'published_at',
                'rank',
                'country_code',
                'plan_type',
                'contact_phone',
                'whatsapp_phone',
                'make_id',
                'admin_approved',
                'model_id',
            ]);

            $listing = Listing::create($common + [
                'category_id' => $section->id(),
                'user_id' => $userId,
            ]);

            $this->syncAttributes($listing, $section, $data['attributes'] ?? []);

            return $listing->fresh('attributes');
        });
    }

    public function update(Section $section, Listing $listing, array $data): Listing
    {
        return DB::transaction(function () use ($section, $listing, $data) {
            $this->normalizeLocationIdsOrFail($data);

            if ($section->supportsMakeModel()) {
                $this->normalizeMakeModel($data);
            } else {
                unset($data['make_id'], $data['model_id'], $data['make'], $data['model']);
            }

            $listing->update(Arr::only($data, [
                'price',
                'description',
                'governorate_id',
                'city_id',
                'lat',
                'lng',
                'address',
                'main_image',
                'images',
                'status',
                'published_at',
                'rank',
                'country_code',
                'plan_type',
                'contact_phone',
                'whatsapp_phone',
                // 'admin_approved',
                'make_id',
                'model_id',
            ]));

            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($listing, $section, $data['attributes'] ?? []);
            }

            return $listing->fresh('attributes');
        });
    }

    protected function normalizeLocationIdsOrFail(array &$data): void
    {
        if (!empty($data['governorate_id']) && !empty($data['city_id']))
            return;

        if (empty($data['governorate_id']) && !empty($data['governorate'])) {
            $govId = DB::table('governorates')->where('name', trim((string) $data['governorate']))->value('id');
            if (!$govId) {
                throw ValidationException::withMessages(['governorate' => ['المحافظة غير معروفة. فضلاً اختر من القائمة.']]);
            }
            $data['governorate_id'] = $govId;
        }

        if (empty($data['city_id']) && !empty($data['city'])) {
            $q = DB::table('cities')->where('name', trim((string) $data['city']));
            if (!empty($data['governorate_id']))
                $q->where('governorate_id', $data['governorate_id']);
            $cityId = $q->value('id');
            if (!$cityId) {
                throw ValidationException::withMessages(['city' => ['المدينة غير معروفة. فضلاً اختر من القائمة أو حدِّد المحافظة لنتائج أدق.']]);
            }
            $data['city_id'] = $cityId;
        }
    }

    protected function normalizeMakeModel(array &$data): void
    {
        if (empty($data['make_id']) && !empty($data['make'])) {
            $makeName = trim((string) $data['make']);

            $makeId = DB::table('makes')
                ->where('name', $makeName)
                ->value('id');

            if (!$makeId) {
                $makeId = DB::table('makes')->insertGetId([
                    'name' => $makeName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $data['make_id'] = $makeId;
        }

        if (empty($data['model_id']) && !empty($data['model']) && !empty($data['make_id'])) {
            $modelName = trim((string) $data['model']);

            $modelId = DB::table('models')
                ->where('make_id', $data['make_id'])
                ->where('name', $modelName)
                ->value('id');

            if (!$modelId) {
                $modelId = DB::table('models')->insertGetId([
                    'make_id' => $data['make_id'],
                    'name' => $modelName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $data['model_id'] = $modelId;
        }
    }

    protected function syncAttributes(Listing $listing, Section $section, array $attrs): void
    {
        $allowedNames = array_column($section->fields, 'field_name');
        $typesByKey = collect($section->fields)
            ->keyBy('field_name')
            ->map(fn($f) => $f['type'] ?? 'string');

        $current = $listing->attributes()
            ->get()
            ->keyBy('key');

        $now = now();

        foreach ($attrs as $key => $value) {
            if (!in_array($key, $allowedNames, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                $listing->attributes()->where('key', $key)->delete();
                continue;
            }

            $type = $typesByKey[$key] ?? 'string';

            $payload = match ($type) {
                'int' => ['value_int' => (int) $value],
                'decimal' => ['value_decimal' => (float) $value],
                'bool' => ['value_bool' => (bool) $value],
                'date' => ['value_date' => $value],
                'json' => ['value_json' => $value],
                default => ['value_string' => (string) $value],
            };

            if ($current->has($key)) {
                $listing->attributes()
                    ->where('key', $key)
                    ->update($payload + [
                        'type' => $type,
                        'updated_at' => $now,
                    ]);
            } else {
                $listing->attributes()->create($payload + [
                    'listing_id' => $listing->id,
                    'key' => $key,
                    'type' => $type,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
