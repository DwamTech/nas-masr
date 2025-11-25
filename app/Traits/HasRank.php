<?php

namespace App\Traits;

use App\Models\Listing;
use Illuminate\Support\Facades\DB;

trait HasRank
{

    public function getNextRank(string $modelClass, int $categoryId): int
    {
        return DB::transaction(function () use ($modelClass, $categoryId) {
            $table = (new $modelClass)->getTable();

            $maxRank = DB::table($table)
                ->where('category_id', $categoryId)
                ->lockForUpdate()
                ->max('rank');

            return ($maxRank ?? 0) + 1;
        });
    }


    public function makeRankOne($categoryId, $adId): bool
    {
        return DB::transaction(function () use ($categoryId, $adId) {

            DB::table('listings')
                ->where('category_id', $categoryId)
                ->select('id')
                ->lockForUpdate()
                ->get();

            $listing = Listing::where('id', $adId)
                ->where('category_id', $categoryId)
                ->first();

            if (!$listing) return false;

            Listing::where('category_id', $categoryId)
                ->where('id', '!=', $adId)
                ->update([
                    'rank'       => DB::raw('IFNULL(`rank`, 0) + 1'),
                    'updated_at' => now()->toDateTimeString(),
                ]);


            $listing->update([
                'rank'       => 1,
                'updated_at' => now(),
            ]);

            return true;
        });
    }
}
