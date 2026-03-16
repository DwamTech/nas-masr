<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryFieldOptionRank;
use App\Models\CategoryMainSection;
use App\Models\Governorate;
use App\Models\Make;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Support\DashboardFilterListsCache;
use App\Support\OptionsHelper;

class DashboardFilterListsController extends Controller
{
    /**
     * @var array<int, array<string, array<string, int>>>
     */
    private array $rankMapsByCategory = [];

    public function governorates(): JsonResponse
    {
        $items = DashboardFilterListsCache::remember(
            DashboardFilterListsCache::governorates(),
            function () {
                return Governorate::query()
                    ->select(['id', 'name', 'sort_order'])
                    ->with([
                        'cities' => function ($query) {
                            $query->select(['id', 'name', 'governorate_id', 'sort_order'])
                                ->orderBy('sort_order')
                                ->orderBy('name');
                        },
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                    ->map(function (Governorate $governorate) {
                        return [
                            'id' => $governorate->id,
                            'name' => $governorate->name,
                            'cities' => $governorate->cities->map(fn ($city) => [
                                'id' => $city->id,
                                'name' => $city->name,
                                'governorate_id' => $city->governorate_id,
                            ])->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();
            }
        );

        return response()->json($items);
    }

    public function sections(Request $request): JsonResponse
    {
        $slug = (string) $request->query('category_slug', '');

        if ($slug === '') {
            return response()->json([
                'message' => 'يجب تحديد القسم بواسطة باراميتر category_slug.',
            ], 422);
        }

        $category = Category::query()->where('slug', $slug)->first();

        if (! $category) {
            return response()->json([
                'message' => 'القسم غير موجود.',
            ], 404);
        }

        $mainSections = DashboardFilterListsCache::remember(
            DashboardFilterListsCache::sections($slug),
            function () use ($category) {
                $mainSections = CategoryMainSection::query()
                    ->select(['id', 'category_id', 'name', 'title', 'sort_order'])
                    ->with([
                        'subSections' => function ($query) {
                            $query->select(['id', 'category_id', 'main_section_id', 'name', 'title', 'sort_order'])
                                ->where('is_active', true)
                                ->orderBy('sort_order');
                        },
                    ])
                    ->where('category_id', $category->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                $mainSections->push((object) [
                    'id' => null,
                    'name' => 'غير ذلك',
                    'subSections' => [],
                    'sort_order' => 9999,
                    'category_id' => $category->id,
                ]);

                return $mainSections->all();
            }
        );

        return response()->json([
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
            ],
            'main_sections' => $mainSections,
        ]);
    }

    public function subSections(CategoryMainSection $mainSection): JsonResponse
    {
        $subSections = DashboardFilterListsCache::remember(
            DashboardFilterListsCache::subSections((int) $mainSection->id),
            function () use ($mainSection) {
                $subSections = $mainSection->subSections()
                    ->select(['id', 'category_id', 'main_section_id', 'name', 'title', 'sort_order'])
                    ->orderBy('sort_order')
                    ->get();

                $subSections->push((object) [
                    'id' => null,
                    'name' => 'غير ذلك',
                    'main_section_id' => $mainSection->id,
                    'category_id' => $mainSection->category_id,
                ]);

                return $subSections->all();
            }
        );

        return response()->json($subSections);
    }

    public function automotive(): JsonResponse
    {
        $categoryId = Category::query()->where('slug', 'cars')->value('id');
        $payload = DashboardFilterListsCache::remember(
            DashboardFilterListsCache::automotive(),
            fn () => $this->buildAutomotivePayload(
                $categoryId ? (int) $categoryId : null,
                null
            )
        );

        return response()->json($payload);
    }

    public function fieldCategory(Request $request): JsonResponse
    {
        $slug = (string) $request->query('category_slug', '');

        if ($slug === '') {
            return response()->json([
                'message' => 'يجب تحديد القسم بواسطة باراميتر category_slug.',
            ], 422);
        }

        $category = Category::query()
            ->select(['id', 'slug', 'name'])
            ->where('slug', $slug)
            ->first();

        if (! $category) {
            return response()->json([
                'message' => 'القسم غير موجود.',
            ], 404);
        }

        $automotiveFallbackCategoryId = null;
        if (in_array($slug, ['cars_rent', 'spare-parts'], true)) {
            $automotiveFallbackCategoryId = Category::query()
                ->where('slug', 'cars')
                ->value('id');
        }

        $categoryIdsToPreload = array_values(array_filter([
            (int) $category->id,
            $automotiveFallbackCategoryId ? (int) $automotiveFallbackCategoryId : null,
        ]));
        $this->preloadRankMaps($categoryIdsToPreload);

        $fields = CategoryField::query()
            ->where('category_slug', $slug)
            ->orderBy('category_slug')
            ->orderBy('sort_order')
            ->get()
            ->map(function (CategoryField $field) use ($category) {
                if (! empty($field->options) && is_array($field->options)) {
                    $field->options = $this->sortOptionsByRank(
                        (int) $category->id,
                        (string) $field->field_name,
                        $field->options
                    );
                }

                return $field;
            });

        $fields = OptionsHelper::processFieldsCollection($fields, false, false)->values();

        $supportsMakeModel = $this->supportsMakeModel($slug);
        $supportsSections = $this->supportsSections($slug);

        $payload = DashboardFilterListsCache::remember(
            DashboardFilterListsCache::fieldCategory($slug),
            function () use ($automotiveFallbackCategoryId, $category, $fields, $supportsMakeModel, $supportsSections) {
                return [
                    'data' => $fields->all(),
                    'makes' => $supportsMakeModel
                        ? $this->buildAutomotivePayload((int) $category->id, $automotiveFallbackCategoryId ? (int) $automotiveFallbackCategoryId : null)
                        : [],
                    'supports_make_model' => $supportsMakeModel,
                    'supports_sections' => $supportsSections,
                    'main_sections' => $supportsSections
                        ? $this->buildMainSectionsPayload((int) $category->id)
                        : [],
                ];
            }
        );

        return response()->json($payload);
    }

    /**
     * @param array<int, string> $fieldNames
     * @param array<int, string> $options
     * @return array<int, string>
     */
    private function sortOptionsByRankWithFallbackFields(int $categoryId, array $fieldNames, array $options): array
    {
        foreach ($fieldNames as $fieldName) {
            $key = trim((string) $fieldName);
            if ($key === '') {
                continue;
            }

            $rankMap = $this->getRankMap($categoryId, $key);
            if (! empty($rankMap)) {
                return $this->sortOptionsByExplicitRankMap($options, $rankMap);
            }
        }

        return $options;
    }

    /**
     * @param array<int, string> $options
     * @return array<int, string>
     */
    private function sortOptionsByRank(int $categoryId, string $fieldName, array $options): array
    {
        $rankMap = $this->getRankMap($categoryId, $fieldName);

        if (empty($rankMap)) {
            return $options;
        }

        return $this->sortOptionsByExplicitRankMap($options, $rankMap);
    }

    /**
     * @param array<int> $categoryIds
     */
    private function preloadRankMaps(array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_filter($categoryIds)));

        if ($categoryIds === []) {
            return;
        }

        $missing = array_values(array_filter($categoryIds, fn (int $id) => ! array_key_exists($id, $this->rankMapsByCategory)));

        if ($missing === []) {
            return;
        }

        $rows = CategoryFieldOptionRank::query()
            ->select(['category_id', 'field_name', 'option_value', 'rank'])
            ->whereIn('category_id', $missing)
            ->orderBy('category_id')
            ->orderBy('field_name')
            ->get();

        foreach ($missing as $categoryId) {
            $this->rankMapsByCategory[$categoryId] = [];
        }

        foreach ($rows as $row) {
            $categoryId = (int) $row->category_id;
            $fieldName = (string) $row->field_name;
            $optionValue = (string) $row->option_value;

            $this->rankMapsByCategory[$categoryId][$fieldName][$optionValue] = (int) $row->rank;
        }
    }

    /**
     * @return array<string, int>
     */
    private function getRankMap(int $categoryId, string $fieldName): array
    {
        $this->preloadRankMaps([$categoryId]);

        return $this->rankMapsByCategory[$categoryId][$fieldName] ?? [];
    }

    /**
     * @param array<int, string> $options
     * @param array<string, int> $rankMap
     * @return array<int, string>
     */
    private function sortOptionsByExplicitRankMap(array $options, array $rankMap): array
    {
        $otherOption = null;
        $withRanks = [];
        $withoutRanks = [];

        foreach ($options as $option) {
            if ($option === 'غير ذلك') {
                $otherOption = $option;
            } elseif (isset($rankMap[$option])) {
                $withRanks[] = ['option' => $option, 'rank' => $rankMap[$option]];
            } else {
                $withoutRanks[] = $option;
            }
        }

        usort($withRanks, fn ($a, $b) => $a['rank'] <=> $b['rank']);

        $sorted = array_map(fn ($item) => $item['option'], $withRanks);
        $result = array_merge($sorted, $withoutRanks);

        if ($otherOption !== null) {
            $result[] = $otherOption;
        }

        return $result;
    }

    private function normalizeRankToken(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        if (! is_string($normalized)) {
            return '';
        }

        return strtolower($normalized);
    }

    private function supportsMakeModel(string $slug): bool
    {
        return in_array($slug, ['cars', 'cars_rent', 'spare-parts'], true);
    }

    private function supportsSections(string $slug): bool
    {
        return in_array($slug, [
            'stores',
            'restaurants',
            'groceries',
            'food-products',
            'electronics',
            'home-appliances',
            'home-tools',
            'furniture',
            'health',
            'education',
            'shipping',
            'mens-clothes',
            'watches-jewelry',
            'free-professions',
            'kids-toys',
            'gym',
            'construction',
            'maintenance',
            'car-services',
            'home-services',
            'lighting-decor',
            'animals',
            'farm-products',
            'wholesale',
            'production-lines',
            'light-vehicles',
            'heavy-transport',
            'tools',
            'missing',
            'spare-parts',
        ], true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAutomotivePayload(?int $primaryCategoryId, ?int $fallbackCategoryId): array
    {
        $items = Make::query()
            ->select(['id', 'name'])
            ->with([
                'models' => function ($query) {
                    $query->select(['id', 'name', 'make_id']);
                },
            ])
            ->get();

        $makes = [];

        foreach ($items as $make) {
            $modelNames = $make->models->pluck('name')->toArray();

            if ($primaryCategoryId) {
                $modelNames = $this->sortOptionsByRankWithCategoryFallback(
                    $primaryCategoryId,
                    $fallbackCategoryId,
                    [
                        "model_make_id_{$make->id}",
                        "car_model_make_id_{$make->id}",
                        'model_' . $this->normalizeRankToken((string) $make->name),
                        'car_model_' . $this->normalizeRankToken((string) $make->name),
                        "model_{$make->name}",
                        "car_model_{$make->name}",
                        "Model_{$make->name}",
                        "CarModel_{$make->name}",
                        "model::{$make->name}",
                        "car_model::{$make->name}",
                        'model',
                        'Model',
                        'car_model',
                        'CarModel',
                    ],
                    $modelNames
                );
            }

            $modelsByName = $make->models->keyBy('name');

            $makes[] = [
                'id' => $make->id,
                'name' => $make->name,
                'models' => collect($modelNames)->values()->map(function ($modelName, $index) use ($modelsByName, $make) {
                    $model = $modelsByName->get($modelName);

                    return [
                        'id' => $model?->id,
                        'name' => $modelName,
                        'make_id' => $make->id,
                        'rank' => $index + 1,
                    ];
                })->all(),
            ];
        }

        if ($primaryCategoryId) {
            $makeNames = array_values(array_map(fn (array $row) => (string) $row['name'], $makes));
            $sortedNames = $this->sortOptionsByRankWithCategoryFallback(
                $primaryCategoryId,
                $fallbackCategoryId,
                ['brand', 'Brand', 'make', 'Make', 'car_make', 'CarMake'],
                $makeNames
            );

            $byName = collect($makes)->keyBy('name');
            $sortedMakes = [];

            foreach ($sortedNames as $name) {
                if ($byName->has($name)) {
                    $sortedMakes[] = $byName->get($name);
                    $byName->forget($name);
                }
            }

            foreach ($byName->values() as $row) {
                $sortedMakes[] = $row;
            }

            $makes = $sortedMakes;
        }

        $payload = collect($makes)->values()->map(function (array $row, int $index) {
            $row['rank'] = $index + 1;
            return $row;
        })->all();

        $payload[] = [
            'id' => null,
            'name' => OptionsHelper::OTHER_OPTION,
            'models' => [],
        ];

        return $payload;
    }

    private function sortOptionsByRankWithCategoryFallback(
        int $primaryCategoryId,
        ?int $fallbackCategoryId,
        array $fieldNames,
        array $options
    ): array {
        if ($this->hasAnyRankForFields($primaryCategoryId, $fieldNames)) {
            return $this->sortOptionsByRankWithFallbackFields($primaryCategoryId, $fieldNames, $options);
        }

        if ($fallbackCategoryId && $fallbackCategoryId !== $primaryCategoryId && $this->hasAnyRankForFields($fallbackCategoryId, $fieldNames)) {
            return $this->sortOptionsByRankWithFallbackFields($fallbackCategoryId, $fieldNames, $options);
        }

        return $options;
    }

    private function hasAnyRankForFields(int $categoryId, array $fieldNames): bool
    {
        foreach ($fieldNames as $fieldName) {
            $key = trim((string) $fieldName);
            if ($key === '') {
                continue;
            }

            if (! empty($this->getRankMap($categoryId, $key))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, mixed>
     */
    private function buildMainSectionsPayload(int $categoryId): array
    {
        $mainSections = CategoryMainSection::query()
            ->select(['id', 'category_id', 'name', 'title', 'sort_order'])
            ->with([
                'subSections' => function ($query) {
                    $query->select(['id', 'category_id', 'main_section_id', 'name', 'title', 'sort_order'])
                        ->where('is_active', true)
                        ->orderBy('sort_order');
                },
            ])
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (CategoryMainSection $mainSection) use ($categoryId) {
                $subSections = $mainSection->subSections->map(function ($subSection) {
                    return [
                        'id' => $subSection->id,
                        'name' => $subSection->name,
                        'title' => $subSection->title,
                    ];
                })->values();

                $subSectionNames = $subSections->pluck('name')->toArray();
                $subSectionNames = $this->sortOptionsByRank(
                    $categoryId,
                    "SubSection_{$mainSection->name}",
                    $subSectionNames
                );
                $processedNames = OptionsHelper::processOptions($subSectionNames, false, false);

                $orderedSubSections = collect($processedNames)->map(function ($name) use ($subSections) {
                    return $subSections->firstWhere('name', $name);
                })->filter()->values();

                return [
                    'id' => $mainSection->id,
                    'name' => $mainSection->name,
                    'title' => $mainSection->title,
                    'sub_sections' => $orderedSubSections->all(),
                ];
            });

        $mainSectionNames = $mainSections->pluck('name')->toArray();
        $mainSectionNames = $this->sortOptionsByRank($categoryId, 'MainSection', $mainSectionNames);
        $processedMainNames = OptionsHelper::processOptions($mainSectionNames, false, false);

        return collect($processedMainNames)->map(function ($name) use ($mainSections) {
            return $mainSections->firstWhere('name', $name);
        })->filter()->values()->all();
    }
}
