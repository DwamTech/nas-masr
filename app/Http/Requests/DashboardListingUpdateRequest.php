<?php

namespace App\Http\Requests;

use App\Models\Listing;
use App\Support\Section;
use Illuminate\Validation\Rule;

class DashboardListingUpdateRequest extends GenericListingRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $key => $ruleSet) {
            $rules[$key] = $this->relaxRuleSet($ruleSet);
        }

        foreach (['main_image', 'images', 'images.*'] as $fileKey) {
            if (isset($rules[$fileKey])) {
                $rules[$fileKey] = $this->ensureSometimesNullable($rules[$fileKey]);
            }
        }

        $section = $this->resolveSection();
        $rules['plan_type'] = $section->slug === 'missing'
            ? ['sometimes', 'string', Rule::in(['free'])]
            : ['sometimes', 'string', Rule::in(['standard', 'premium', 'featured', 'free'])];

        return $rules;
    }

    protected function resolveSection(): Section
    {
        $listing = $this->route('listing');

        if ($listing instanceof Listing) {
            return Section::fromId((int) $listing->category_id);
        }

        return parent::resolveSection();
    }
}
