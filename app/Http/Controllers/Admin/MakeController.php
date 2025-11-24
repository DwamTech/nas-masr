<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Make;
use App\Models\CarModel;
use Illuminate\Http\Request;

class MakeController extends Controller
{
    public function index()
    {
        $items = Make::with('models')->orderBy('name')->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'], // شيلنا unique هنا
            'models'   => ['nullable', 'array'],
            'models.*' => ['string', 'max:191'],
        ]);

        // 1) نحاول نلاقي make موجودة بنفس الاسم
        $make = Make::where('name', $data['name'])->first();

        $isNew = false;

        if (! $make) {
            // مافيش make بالاسم ده → نعمل create جديد
            $make = Make::create(['name' => $data['name']]);
            $isNew = true;
        } else {
            // لو حابة تسمحي بتعديل الاسم نفسه (نادراً)، تقدري تعملي:
            // $make->update(['name' => $data['name']]);
        }

        // 2) نجهز الموديلات اللي جاية من الريكوست
        $models = collect($data['models'] ?? [])
            ->map(fn($m) => trim($m))
            ->filter()          // شيل الفاضي
            ->unique()          // شيل التكرار
            ->values();         // ريسيت للـ index

        $existing = $make->models()
            ->pluck('id', 'name');

        $keepIds = [];

        foreach ($models as $modelName) {
            if (isset($existing[$modelName])) {
                // الموديل موجود بالفعل بنفس الاسم → نخليه
                $keepIds[] = $existing[$modelName];
            } else {
                // موديل جديد → نضيفه
                $model = $make->models()->create([
                    'name' => $modelName,
                ]);
                $keepIds[] = $model->id;
            }
        }


        if (count($keepIds) > 0) {
            $make->models()
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            // لو مبعتيش ولا موديل → امسح كل الموديلات القديمة
            $make->models()->delete();
        }


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
        $make->delete();
        return response()->json(null, 204);
    }

    public function models(Make $make)
    {
        return response()->json($make->models()->orderBy('name')->get());
    }

    public function addModel(Request $request, Make $make)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:models,name,NULL,id,make_id,' . $make->id],
        ]);

        $model = CarModel::create([
            'name' => $data['name'],
            'make_id' => $make->id,
        ]);

        return response()->json($model, 201);
    }

    public function updateModel(Request $request, CarModel $model)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', 'unique:models,name,' . $model->id . ',id,make_id,' . $model->make_id],
            'make_id' => ['sometimes', 'integer', 'exists:makes,id'],
        ]);

        $model->update($data);
        return response()->json($model);
    }

    public function deleteModel(CarModel $model)
    {
        $model->delete();
        return response()->json(null, 204);
    }
}
