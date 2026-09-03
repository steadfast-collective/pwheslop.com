<?php

namespace App\Feed\Sources;

use App\Feed\FeedItem;

interface Source
{
    /**
     * A short, stable identifier used for cache keys.
     */
    public function key(): string;

    /**
     * Fetch and parse the remote feed.
     *
     * @return FeedItem[]
     *
     * @throws \Throwable when the feed can't be fetched or parsed.
     */
    public function fetch(): array;
}
