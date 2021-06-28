<?php

namespace App\Tags;

use Statamic\Facades\Asset;
use Statamic\Facades\Data;
use Statamic\Tags\Tags;

class BardFirstImage extends Tags
{
    /**
     * The {{ bard_first_image }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        $bardField = $this->context->get('page_content');

        if (! $bardField) {
            return;
        }

        $images = collect($bardField->raw())
            ->where('type', 'paragraph')
            ->map(function ($paragraphNode) {
                if (! isset($paragraphNode['content'])) {
                    return;
                }

                return collect($paragraphNode['content'])
                    ->where('type', 'image')
                    ->map(function ($imageItem) {
                        return Data::find($imageItem['attrs']['src']);
                    })
                    ->flatten()
                    ->all();
            })
            ->flatten()
            ->reject(function ($i) {
                return $i == null;
            });

        if ($images->count() > 0) {
            return config('app.url') . $images->first()->url();
        }

        return;
    }
}
