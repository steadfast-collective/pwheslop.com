<?php

namespace Tests\Unit\Feed;

use App\Feed\FeedItem;
use App\Feed\Sources\GhostSource;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GhostSourceTest extends TestCase
{
    protected function source(): GhostSource
    {
        return new GhostSource('platforming_community', 'Platforming Community', 'https://platformingcommunity.com/rss/');
    }

    public function test_it_maps_ghost_rss_items_to_feed_items()
    {
        Http::fake([
            'platformingcommunity.com/rss/' => Http::response(file_get_contents(__DIR__.'/../../Fixtures/ghost-rss.xml')),
        ]);

        $items = $this->source()->fetch();

        $this->assertCount(2, $items);

        $first = $items[0];
        $this->assertInstanceOf(FeedItem::class, $first);
        $this->assertSame(FeedItem::TYPE_ARTICLE, $first->type);
        $this->assertSame('Flywheels and motion.', $first->title);
        $this->assertSame('https://platformingcommunity.com/flywheels-and-motion/', $first->url);
        $this->assertSame('2025-08-12 08:00:00', $first->date->utc()->format('Y-m-d H:i:s'));
        $this->assertSame("Communities gather momentum slowly & then all at once. Here's how to think about the flywheel.", $first->summary);
        $this->assertSame('https://platformingcommunity.com/content/images/2025/08/flywheel.jpg', $first->thumbnail);
        $this->assertSame('Platforming Community', $first->source);
        $this->assertTrue($first->external);
    }

    public function test_it_falls_back_to_encoded_content_when_description_is_empty()
    {
        Http::fake([
            'platformingcommunity.com/rss/' => Http::response(file_get_contents(__DIR__.'/../../Fixtures/ghost-rss.xml')),
        ]);

        $second = $this->source()->fetch()[1];

        $this->assertSame('Community Building - the LEGO way.', $second->title);
        $this->assertSame('What a box of bricks can teach us about modular community design. Second paragraph.', $second->summary);
        $this->assertNull($second->thumbnail);
    }

    public function test_it_throws_on_http_errors()
    {
        Http::fake(['platformingcommunity.com/rss/' => Http::response('', 503)]);

        $this->expectException(RequestException::class);

        $this->source()->fetch();
    }

    public function test_it_throws_on_invalid_xml()
    {
        Http::fake(['platformingcommunity.com/rss/' => Http::response('<html>not a feed')]);

        $this->expectException(\RuntimeException::class);

        $this->source()->fetch();
    }
}
