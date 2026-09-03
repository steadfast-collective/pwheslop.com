<?php

namespace Tests\Unit\Feed;

use App\Feed\FeedAggregator;
use App\Feed\FeedItem;
use App\Feed\Sources\GhostSource;
use App\Feed\Sources\Source;
use App\Feed\Sources\YouTubeSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class FeedAggregatorTest extends TestCase
{
    protected function item(string $title, string $date, string $type = FeedItem::TYPE_ARTICLE): FeedItem
    {
        return new FeedItem($type, $title, 'https://example.com/'.$title, Carbon::parse($date), external: $type !== FeedItem::TYPE_POST);
    }

    /**
     * @param  FeedItem[]|\Throwable  $result
     */
    protected function source(string $key, $result): Source
    {
        return new class($key, $result) implements Source
        {
            public int $fetches = 0;

            public function __construct(protected string $key, protected $result)
            {
            }

            public function key(): string
            {
                return $this->key;
            }

            public function fetch(): array
            {
                $this->fetches++;

                if ($this->result instanceof \Throwable) {
                    throw $this->result;
                }

                return $this->result;
            }
        };
    }

    public function test_it_builds_sources_from_config()
    {
        $aggregator = FeedAggregator::fromConfig(require base_path('config/feed.php'));

        $sources = $aggregator->sources();

        $this->assertCount(2, $sources);
        $this->assertInstanceOf(GhostSource::class, $sources[0]);
        $this->assertSame('https://platformingcommunity.com/rss/', $sources[0]->url());
        $this->assertInstanceOf(YouTubeSource::class, $sources[1]);
        $this->assertStringContainsString('channel_id=UC', $sources[1]->url());
    }

    public function test_remote_items_are_fetched_once_and_then_cached()
    {
        $source = $this->source('a', [$this->item('one', '2025-01-01')]);
        $aggregator = new FeedAggregator([$source], ttl: 3600);

        $this->assertCount(1, $aggregator->remote());
        $this->assertCount(1, $aggregator->remote());
        $this->assertSame(1, $source->fetches);
        $this->assertSame('one', Cache::get('feed.a')[0]['title']);
    }

    public function test_a_failing_source_yields_nothing_and_is_not_retried_on_every_request()
    {
        $source = $this->source('broken', new RuntimeException('boom'));
        $aggregator = new FeedAggregator([$source], ttl: 3600, failureTtl: 300);

        $this->assertCount(0, $aggregator->remote());
        $this->assertCount(0, $aggregator->remote());
        $this->assertSame(1, $source->fetches);
    }

    public function test_a_failing_source_falls_back_to_its_last_good_result()
    {
        Cache::forever('feed.broken.last_good', [$this->item('old', '2024-01-01')->toArray()]);

        $aggregator = new FeedAggregator([$this->source('broken', new RuntimeException('boom'))]);

        $items = $aggregator->remote();

        $this->assertCount(1, $items);
        $this->assertSame('old', $items[0]['title']);
    }

    public function test_refresh_refetches_every_source_and_reports_counts()
    {
        $good = $this->source('good', [$this->item('one', '2025-01-01'), $this->item('two', '2025-02-01')]);
        $bad = $this->source('bad', new RuntimeException('boom'));
        $aggregator = new FeedAggregator([$good, $bad]);

        $aggregator->remote();
        $results = $aggregator->refresh();

        $this->assertSame(['good' => 2, 'bad' => null], $results);
        $this->assertSame(2, $good->fetches);
    }

    public function test_merge_sorts_everything_newest_first()
    {
        $aggregator = new FeedAggregator([]);

        $merged = $aggregator->merge(
            [$this->item('middle', '2025-05-01', FeedItem::TYPE_POST)],
            collect([$this->item('newest', '2025-09-01', FeedItem::TYPE_VIDEO)->toArray()]),
            [$this->item('oldest', '2025-01-01')],
        );

        $this->assertSame(['newest', 'middle', 'oldest'], $merged->pluck('title')->all());
        $this->assertTrue($merged[0]['is_video']);
        $this->assertTrue($merged[1]['is_post']);
        $this->assertFalse($merged[1]['external']);
    }
}
