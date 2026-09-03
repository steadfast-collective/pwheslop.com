<?php

namespace App\Tags;

use App\Feed\FeedAggregator;
use App\Feed\FeedItem;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Tags\Tags;

class Feed extends Tags
{
    /**
     * The {{ feed }} tag.
     *
     * Merges published posts with articles and videos published elsewhere,
     * newest first. Accepts an optional `limit` parameter.
     *
     * @return array
     */
    public function index()
    {
        $aggregator = app(FeedAggregator::class);

        $items = $aggregator->merge($this->posts(), $aggregator->remote());

        if ($limit = (int) $this->params->get('limit')) {
            $items = $items->take($limit);
        }

        return $items->values()->all();
    }

    /**
     * @return FeedItem[]
     */
    protected function posts(): array
    {
        return EntryFacade::query()
            ->where('collection', 'posts')
            ->get()
            ->filter(fn (Entry $entry) => $entry->status() === 'published')
            ->map(fn (Entry $entry) => new FeedItem(
                type: FeedItem::TYPE_POST,
                title: (string) $entry->get('title'),
                url: $entry->url(),
                date: $entry->date(),
                summary: $this->summary($entry),
            ))
            ->values()
            ->all();
    }

    protected function summary(Entry $entry): ?string
    {
        if ($summary = $entry->get('summary')) {
            return FeedItem::summarize($summary);
        }

        return FeedItem::summarize((string) $entry->augmentedValue('page_content'));
    }
}
