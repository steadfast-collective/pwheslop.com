<?php

namespace App\Feed;

use App\Feed\Sources\GhostSource;
use App\Feed\Sources\Source;
use App\Feed\Sources\YouTubeSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches (and caches) every remote source and merges the results into a
 * single, date-ordered feed.
 */
class FeedAggregator
{
    /**
     * @param  Source[]  $sources
     */
    public function __construct(
        protected array $sources,
        protected int $ttl = 3600,
        protected int $failureTtl = 300,
    ) {
    }

    /**
     * Build an aggregator from config/feed.php.
     */
    public static function fromConfig(array $config): self
    {
        $timeout = (int) ($config['timeout'] ?? 5);
        $sources = [];

        $ghost = $config['sources']['platforming_community'] ?? null;
        if (! empty($ghost['url'])) {
            $sources[] = new GhostSource(
                key: 'platforming_community',
                label: $ghost['label'] ?? 'Platforming Community',
                url: $ghost['url'],
                timeout: $timeout,
            );
        }

        $youtube = $config['sources']['youtube'] ?? null;
        if (! empty($youtube['url']) || ! empty($youtube['channel_id'])) {
            $sources[] = new YouTubeSource(
                key: 'youtube',
                label: $youtube['label'] ?? 'YouTube',
                url: $youtube['url'] ?? YouTubeSource::feedUrl($youtube['channel_id']),
                timeout: $timeout,
            );
        }

        return new self(
            sources: $sources,
            ttl: (int) ($config['cache_ttl'] ?? 3600),
            failureTtl: (int) ($config['failure_ttl'] ?? 300),
        );
    }

    /**
     * @return Source[]
     */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * All remote items (as arrays), served from cache where possible.
     */
    public function remote(): Collection
    {
        return collect($this->sources)->flatMap(fn (Source $source) => $this->cached($source));
    }

    /**
     * Merge any number of item lists, newest first.
     */
    public function merge(iterable ...$lists): Collection
    {
        return collect($lists)
            ->flatMap(fn ($list) => collect($list)->map(
                fn ($item) => $item instanceof FeedItem ? $item->toArray() : $item
            ))
            ->sortByDesc(fn (array $item) => $item['date']->getTimestamp())
            ->values();
    }

    /**
     * Drop the cache for every source and fetch fresh copies.
     *
     * @return array<string, int|null> item counts per source, null on failure
     */
    public function refresh(): array
    {
        $results = [];

        foreach ($this->sources as $source) {
            Cache::forget($this->cacheKey($source));

            $items = $this->fetch($source);

            $results[$source->key()] = $items === null ? null : count($items);
        }

        return $results;
    }

    protected function cached(Source $source): array
    {
        $cached = Cache::get($this->cacheKey($source));

        if (is_array($cached)) {
            return $cached;
        }

        return $this->fetch($source) ?? Cache::get($this->lastGoodKey($source), []);
    }

    /**
     * Fetch a source, updating the cache. Returns null when the fetch failed,
     * in which case the last good result is re-cached briefly so a broken
     * upstream doesn't slow every page load down.
     */
    protected function fetch(Source $source): ?array
    {
        try {
            $items = array_map(fn (FeedItem $item) => $item->toArray(), $source->fetch());
        } catch (Throwable $e) {
            Log::warning('Feed source failed', [
                'source' => $source->key(),
                'error' => $e->getMessage(),
            ]);

            Cache::put(
                $this->cacheKey($source),
                Cache::get($this->lastGoodKey($source), []),
                $this->failureTtl
            );

            return null;
        }

        Cache::put($this->cacheKey($source), $items, $this->ttl);
        Cache::forever($this->lastGoodKey($source), $items);

        return $items;
    }

    protected function cacheKey(Source $source): string
    {
        return 'feed.'.$source->key();
    }

    protected function lastGoodKey(Source $source): string
    {
        return 'feed.'.$source->key().'.last_good';
    }
}
