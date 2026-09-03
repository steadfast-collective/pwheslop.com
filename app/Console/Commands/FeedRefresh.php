<?php

namespace App\Console\Commands;

use App\Feed\FeedAggregator;
use Illuminate\Console\Command;

class FeedRefresh extends Command
{
    protected $signature = 'feed:refresh';

    protected $description = 'Re-fetch the remote homepage feed sources and warm the cache';

    public function handle(FeedAggregator $aggregator): int
    {
        foreach ($aggregator->refresh() as $source => $count) {
            if ($count === null) {
                $this->warn(sprintf('%s: fetch failed, serving last good copy (see log)', $source));
            } else {
                $this->info(sprintf('%s: %d items', $source, $count));
            }
        }

        return self::SUCCESS;
    }
}
