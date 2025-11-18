<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenericListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\ListingService;
use App\Support\Section;
use App\Traits\HasRank;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    use HasRank;
    public function index(string $section, Request $request): AnonymousResourceCollection
    {
        $sec = Section::fromSlug($section);

        $typesByKey = Listing::typesByKeyForSection($sec);

        $filterableKeys = collect($sec->fields)
            ->where('filterable', true)
            ->pluck('field_name')
            ->all();

        $q = Listing::query()
            ->forSection($sec)
            ->with(['attributes', 'governorate', 'city', 'make', 'model'])
            ->orderByDesc('rank')
            ->keyword($request->query('q'))
            ->filterGovernorate($request->query('governorate_id'), $request->query('governorate'))
            ->filterCity($request->query('city_id'), $request->query('city'))
            ->priceRange($request->query('price_min'), $request->query('price_max'));

        if ($request->filled('status')) {
            $q->statusIs($request->query('status'));
        } else {
            $q->active();
        }

        if ($plan = $request->query('plan_type')) {
            $q->where('plan_type', $plan);
        }


        $attrEq = (array) $request->query('attr', []);
        $attrEq = array_intersect_key($attrEq, array_flip($filterableKeys));
        $q->attrEq($attrEq, $typesByKey);

        $attrIn = (array) $request->query('attr_in', []);
        $attrIn = array_intersect_key($attrIn, array_flip($filterableKeys));
        $q->attrIn($attrIn, $typesByKey);

        $attrMin = (array) $request->query('attr_min', []);
        $attrMax = (array) $request->query('attr_max', []);
        $attrMin = array_intersect_key($attrMin, array_flip($filterableKeys));
        $attrMax = array_intersect_key($attrMax, array_flip($filterableKeys));
        $q->attrRange($attrMin, $attrMax, $typesByKey);

        $attrLike = (array) $request->query('attr_like', []);
        $attrLike = array_intersect_key($attrLike, array_flip($filterableKeys));
        $q->attrLike($attrLike);


        return ListingResource::collection(
            $q->paginate(15)->appends($request->query())
        );
    }


    public function store(string $section, GenericListingRequest $request, ListingService $service): ListingResource
    {
        $sec = Section::fromSlug($section);
        $data = $request->validated();

        $sec = Section::fromSlug($section);

        $rank = $this->getNextRank(Listing::class, $sec->id());
        $data['rank'] = $rank;
        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->storeUploaded($request->file('main_image'), $section, 'main');
        }
        if ($request->hasFile('images')) {
            $stored = [];
            foreach ($request->file('images') as $file) {
                $stored[] = $this->storeUploaded($file, $section, 'gallery');
            }
            $data['images'] = $stored;
        }

        $listing = $service->create($sec, $data, $request->user()->id ?? 1);

        return new ListingResource($listing->load(['attributes', 'governorate', 'city', 'make', 'model']));
    }

    public function show(string $section, Listing $listing): ListingResource
    {
        $sec = Section::fromSlug($section);
        abort_if($listing->category_id !== $sec->id(), 404);
        $listing->increment('views');

        return new ListingResource($listing->load(['attributes', 'governorate', 'city', 'make', 'model']));
    }

    public function update(string $section, GenericListingRequest $request, Listing $listing, ListingService $service): ListingResource
    {
        $sec = Section::fromSlug($section);
        abort_if($listing->category_id !== $sec->id(), 404);

        $data = $request->validated();

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->storeUploaded($request->file('main_image'), $section, 'main');
        }
        if ($request->hasFile('images')) {
            $stored = [];
            foreach ($request->file('images') as $file) {
                $stored[] = $this->storeUploaded($file, $section, 'gallery');
            }
            $data['images'] = $stored;
        }

        $listing = $service->update($sec, $listing, $data);

        return new ListingResource($listing->load(['attributes', 'governorate', 'city', 'make', 'model']));
    }

    public function destroy(string $section, Listing $listing)
    {
        $sec = Section::fromSlug($section);
        abort_if($listing->category_id !== $sec->id(), 404);

        $listing->delete();

        return response()->json(['ok' => true]);
    }

    protected function storeUploaded($file, string $section, string $bucket = 'main'): string
    {
        $datePath = now()->format('Y/m');
        $dir = "uploads/{$section}/{$datePath}/" . ($bucket === 'main' ? 'main' : 'gallery');
        $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->makeDirectory($dir);
        $path = $file->storeAs($dir, $name, 'public');
        if (!$path) {
            $field = $bucket === 'main' ? 'main_image' : 'images';
            throw \Illuminate\Validation\ValidationException::withMessages([$field => ['فشل رفع الملف.']]);
        }
        if (!Storage::disk('public')->exists($path)) {
            $field = $bucket === 'main' ? 'main_image' : 'images';
            throw \Illuminate\Validation\ValidationException::withMessages([$field => ['فشل حفظ الملف.']]);
        }
        return $path;
    }
}
