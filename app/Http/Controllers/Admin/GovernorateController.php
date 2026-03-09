<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Models\City;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    public function index()
    {
        $items = Governorate::with('cities')->orderBy('sort_order')->orderBy('name')->get();
        $items->push((object)[
            'id' => null,
            'name' => 'غير ذلك',
            'cities' => []
        ]);
        return response()->json($items);
    }

    public function storeGov(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:governorates,name'],
        ]);

        $gov = Governorate::create(['name' => $data['name']]);

        // إضافة مدينة "غير ذلك" تلقائيًا
        City::create([
            'name' => 'غير ذلك',
            'governorate_id' => $gov->id,
        ]);

        return response()->json($gov->load('cities'), 201);
    }
    public function storCities(Request $request, Governorate $governorate)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:cities,name,NULL,id,governorate_id,' . $governorate->id],
        ]);

        $city = City::create([
            'name' => $data['name'],
            'governorate_id' => $governorate->id,
        ]);

        return response()->json($city, 201);
    }

    public function updateGov(Request $request, Governorate $governorate)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', 'unique:governorates,name,' . $governorate->id],
        ]);

        if (array_key_exists('name', $data)) {
            $governorate->update(['name' => $data['name']]);
        }

        return response()->json($governorate->load('cities'));
    }

    public function destroyGov(Governorate $governorate)
    {
        $adsCount = $governorate->listings()->count();

        if ($adsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المحافظة لأنها مستخدمة في الإعلانات.'
            ], 400);
        }

        $governorate->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المحافظة بنجاح'
        ]);
    }


    public function cities(Governorate $governorate)
    {
        $cities = $governorate->cities()->orderBy('sort_order')->orderBy('name')->get();
        $cities->push((object)[
            'id' => null,
            'name' => 'غير ذلك',
            'governorate_id' => $governorate->id
        ]);
        return response()->json($cities);
    }

    // public function addCity(Request $request, Governorate $governorate)
    // {
    //     $data = $request->validate([
    //         'name' => ['required', 'string', 'max:191', 'unique:cities,name,NULL,id,governorate_id,' . $governorate->id],
    //     ]);

    //     $city = City::create([
    //         'name' => $data['name'],
    //         'governorate_id' => $governorate->id,
    //     ]);

    //     return response()->json($city, 201);
    // }

    public function updateCity(Request $request, City $city)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', 'unique:cities,name,' . $city->id . ',id,governorate_id,' . $city->governorate_id],
            'governorate_id' => ['sometimes', 'integer', 'exists:governorates,id'],
        ]);

        $city->update($data);
        return response()->json($city);
    }

    public function deleteCity(City $city)
    {

        $adsCount = $city->listings()->count();

        if ($adsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المدينة لأنها مستخدمة في الإعلانات.',
            ], 400);
        }

        $city->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المدينة بنجاح.',
        ], 200);
    }

    // POST /api/admin/governorates/ranks
    public function updateGovRanks(Request $request)
    {
        $data = $request->validate([
            'ranks' => 'required|array',
            'ranks.*.id' => 'required|integer|exists:governorates,id',
            'ranks.*.rank' => 'required|integer|min:0',
        ]);

        foreach ($data['ranks'] as $item) {
            Governorate::where('id', $item['id'])->update(['sort_order' => $item['rank']]);
        }

        return response()->json(['message' => 'تم تحديث ترتيب المحافظات بنجاح']);
    }

    // POST /api/admin/cities/ranks
    public function updateCityRanks(Request $request)
    {
        $data = $request->validate([
            'ranks' => 'required|array',
            'ranks.*.id' => 'required|integer|exists:cities,id',
            'ranks.*.rank' => 'required|integer|min:0',
        ]);

        foreach ($data['ranks'] as $item) {
            City::where('id', $item['id'])->update(['sort_order' => $item['rank']]);
        }

        return response()->json(['message' => 'تم تحديث ترتيب المدن بنجاح']);
    }

    /**
     * GET /api/admin/cities/mappings
     * جلب mapping للمدن مع IDs الخاصة بها منظم حسب المحافظة
     */
    public function getCitiesMappings()
    {
        $governorates = Governorate::with('cities')->orderBy('name')->get();

        $byGovernorateId = [];
        $byGovernorateNam = [];

        foreach ($governorates as $gov) {
            $citiesById = [];
            $citiesByName = [];

            foreach ($gov->cities as $city) {
                $citiesById[$city->name] = $city->id;
                $citiesByName[$city->name] = $city->id;
            }

            // Map by governorate ID
            $byGovernorateId[(string)$gov->id] = $citiesById;

            // Map by governorate name
            $byGovernorateNam[$gov->name] = $citiesByName;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'by_governorate_id' => $byGovernorateId,
                'by_governorate_name' => $byGovernorateNam,
            ]
        ]);
    }
}

