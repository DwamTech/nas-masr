<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Make;
use App\Models\CarModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class MakeController extends Controller
{
    public function index()
    {
        $items = Make::with('models')->orderBy('name')->get();
        $items->push((object)[
            'id' => null,
            'name' => 'غير ذلك',
            'models' => []
        ]);
        return response()->json($items);
    }

    public function addMake(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'], // شيلنا unique هنا
            // 'models'   => ['nullable', 'array'],
            // 'models.*' => ['string', 'max:191'],
        ]);

        $make = Make::where('name', $data['name'])->first();

        $isNew = false;

        if (!$make) {
            $make = Make::create(['name' => $data['name']]);
            $isNew = true;
        } else {
            return response()->json([
                'message' => 'Make with this name already exists.',
            ], 422);
        }

        // // 2) نجهز الموديلات اللي جاية من الريكوست
        // $models = collect($data['models'] ?? [])
        //     ->map(fn($m) => trim($m))
        //     ->filter()          // شيل الفاضي
        //     ->unique()          // شيل التكرار
        //     ->values();         // ريسيت للـ index

        // $existing = $make->models()
        //     ->pluck('id', 'name');

        // $keepIds = [];

        // foreach ($models as $modelName) {
        //     if (isset($existing[$modelName])) {
        //         // الموديل موجود بالفعل بنفس الاسم → نخليه
        //         $keepIds[] = $existing[$modelName];
        //     } else {
        //         // موديل جديد → نضيفه
        //         $model = $make->models()->create([
        //             'name' => $modelName,
        //         ]);
        //         $keepIds[] = $model->id;
        //     }
        // }


        // if (count($keepIds) > 0) {
        //     $make->models()
        //         ->whereNotIn('id', $keepIds)
        //         ->delete();
        // } else {
        //     // لو مبعتيش ولا موديل → امسح كل الموديلات القديمة
        //     $make->models()->delete();
        // }


        $make->load('models');

        return response()->json($make, $isNew ? 201 : 200);
    }

    public function update(Request $request, Make $make)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', 'unique:makes,name,' . $make->id],
        ]);

        if (array_key_exists('name', $data)) {
            $make->update(['name' => $data['name']]);
        }

        return response()->json($make->load('models'));
    }

    public function destroy(Make $make)
    {
        // كل الموديلات اللي تحت الماركة دي
        $modelIds = $make->models()->pluck('id');

        $isUsed = Listing::where('make_id', $make->id)
            ->orWhereIn('model_id', $modelIds)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'message' => 'لا يمكن حذف هذه الماركة لأنها مرتبطة بإعلانات أو موديلات مستخدمة في إعلانات.',
            ], 422);
        }

        // لو حابة كمان يتم حذف كل الموديلات التابعة ليها:
        $make->models()->delete();

        $make->delete();

        return response()->json("Deleted successfully", 204);
    }

    public function models(Make $make)
    {
        $models = $make->models()->orderBy('name')->get();
        $models->push((object)[
            'id' => null,
            'name' => 'غير ذلك',
            'make_id' => $make->id
        ]);
        return response()->json($models);
    }

    public function addModel(Request $request, Make $make)
    {
        // ✅ Validate
        $data = $request->validate([
            'models' => ['required', 'array', 'min:1'],
            'models.*' => [
                'required',
                'string',
                'max:191',
                // Unique per make
                Rule::unique('models', 'name')->where(function ($q) use ($make) {
                    return $q->where('make_id', $make->id);
                }),
            ],
        ]);

        $createdModels = [];

        // ✅ Create all models
        foreach ($data['models'] as $name) {
            $createdModels[] = CarModel::create([
                'name' => $name,
                'make_id' => $make->id,
            ]);
        }

        // ✅ Response بالشكل اللي تحبيه
        return response()->json([
            'make_id' => $make->id,
            'models' => $createdModels,
        ], 201);
    }

    public function updateModel(Request $request, CarModel $model)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:models,name,' . $model->id . ',id,make_id,' . $model->make_id],
            'make_id' => ['required', 'integer', 'exists:makes,id'],
        ]);

        $model->update($data);
        return response()->json($model);
    }

    public function deleteModel(CarModel $model)
    {
        $isUsed = Listing::where('model_id', $model->id)->exists();

        if ($isUsed) {
            return response()->json([
                'message' => 'لا يمكن حذف هذا الموديل لأنه مستخدم في إعلانات حالية.',
            ], 422);
        }

        $model->delete();

        return response()->json("Deleted successfully", 204);
    }
}
