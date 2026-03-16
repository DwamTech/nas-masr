<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryFieldOptionRank;
use Illuminate\Support\Facades\DB;

class OptionRankService
{
    /**
     * Update ranks for options in a specific field.
     *
     * @param string $categorySlug
     * @param string $fieldName
     * @param array $ranks Array of ['option' => string, 'rank' => int]
     * @return bool
     * @throws \Exception
     */
    public function updateRanks(string $categorySlug, string $fieldName, array $ranks): bool
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();

        // Validate ranks
        $this->validateRanks($ranks);

        DB::beginTransaction();
        try {
            foreach ($ranks as $rankData) {
                CategoryFieldOptionRank::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'field_name' => $fieldName,
                        'option_value' => $rankData['option'],
                    ],
                    [
                        'rank' => $rankData['rank'],
                    ]
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get options with ranks for a field.
     *
     * @param string $categorySlug
     * @param string $fieldName
     * @return array
     */
    public function getOptionsWithRanks(string $categorySlug, string $fieldName): array
    {
        $category = Category::where('slug', $categorySlug)->first();
        
        if (!$category) {
            return [];
        }

        return CategoryFieldOptionRank::forField($category->id, $fieldName)
            ->get()
            ->map(function ($rank) {
                return [
                    'option' => $rank->option_value,
                    'rank' => $rank->rank,
                ];
            })
            ->toArray();
    }

    /**
     * Assign default ranks to existing options.
     *
     * @param string $categorySlug
     * @param string $fieldName
     * @param array $options
     * @return void
     */
    public function assignDefaultRanks(string $categorySlug, string $fieldName, array $options): void
    {
        $category = Category::where('slug', $categorySlug)->first();
        
        if (!$category) {
            return;
        }

        // Ensure "غير ذلك" is last
        $options = $this->ensureOtherIsLast($options);

        DB::beginTransaction();
        try {
            foreach ($options as $index => $option) {
                CategoryFieldOptionRank::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'field_name' => $fieldName,
                        'option_value' => $option,
                    ],
                    [
                        'rank' => $index + 1,
                    ]
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate rank uniqueness within the array.
     *
     * @param array $ranks
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateRankUniqueness(array $ranks): void
    {
        $rankValues = array_column($ranks, 'rank');
        $uniqueRanks = array_unique($rankValues);

        if (count($rankValues) !== count($uniqueRanks)) {
            throw new \InvalidArgumentException('قيم الترتيب يجب أن تكون فريدة');
        }
    }

    /**
     * Validate that ranks are sequential starting from 1.
     *
     * @param array $ranks
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateSequentialRanks(array $ranks): void
    {
        $rankValues = array_column($ranks, 'rank');
        sort($rankValues);
        
        $expectedRanks = range(1, count($ranks));

        if ($rankValues !== $expectedRanks) {
            throw new \InvalidArgumentException('قيم الترتيب يجب أن تكون متسلسلة بدءاً من 1');
        }
    }

    /**
     * Validate all rank requirements.
     *
     * @param array $ranks
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateRanks(array $ranks): void
    {
        $this->validateRankUniqueness($ranks);
        $this->validateSequentialRanks($ranks);
        $this->ensureOtherHasHighestRank($ranks);
    }

    /**
     * Ensure "غير ذلك" has the highest rank.
     *
     * @param array $ranks
     * @return void
     * @throws \InvalidArgumentException
     */
    private function ensureOtherHasHighestRank(array $ranks): void
    {
        $otherOption = null;
        $maxRank = 0;

        foreach ($ranks as $rankData) {
            if ($rankData['option'] === 'غير ذلك') {
                $otherOption = $rankData;
            }
            $maxRank = max($maxRank, $rankData['rank']);
        }

        if ($otherOption && $otherOption['rank'] !== $maxRank) {
            throw new \InvalidArgumentException('خيار "غير ذلك" يجب أن يكون في الأسفل دائماً');
        }
    }

    /**
     * Ensure "غير ذلك" is at the end of the array.
     *
     * @param array $options
     * @return array
     */
    private function ensureOtherIsLast(array $options): array
    {
        $otherIndex = array_search('غير ذلك', $options);
        
        if ($otherIndex !== false) {
            // Remove "غير ذلك" from its current position
            unset($options[$otherIndex]);
            // Re-index array
            $options = array_values($options);
            // Add "غير ذلك" at the end
            $options[] = 'غير ذلك';
        }

        return $options;
    }
}
