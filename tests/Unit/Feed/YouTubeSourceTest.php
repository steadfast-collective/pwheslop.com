<?php

namespace Tests\Unit\Feed;

use App\Feed\FeedItem;
use App\Feed\Sources\YouTubeSource;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeSourceTest extends TestCase
{
    public function test_it_builds_the_channel_feed_url()
    {
        $this->assertSame(
            'https://www.youtube.com/feeds/videos.xml?channel_id=UC2wqdXFTQRDh2ZamjoH3NHQ',
            YouTubeSource::feedUrl('UC2wqdXFTQRDh2ZamjoH3NHQ')
        );
    }

    public function test_it_maps_youtube_atom_entries_to_feed_items()
    {
        Http::fake([
            'www.youtube.com/feeds/videos.xml*' => Http::response(file_get_contents(__DIR__.'/../../Fixtures/youtube-atom.xml')),
        ]);

        $source = new YouTubeSource('youtube', 'YouTube', YouTubeSource::feedUrl('UC2wqdXFTQRDh2ZamjoH3NHQ'));

        $items = $source->fetch();

        $this->assertCount(2, $items);

        $first = $items[0];
        $this->assertSame(FeedItem::TYPE_VIDEO, $first->type);
        $this->assertSame('Building community platforms with Laravel', $first->title);
        $this->assertSame('https://www.youtube.com/watch?v=abc123DEF45', $first->url);
        $this->assertSame('2025-09-01 09:00:00', $first->date->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('In this video we look at how Steadfast Collective approaches community platforms. Chapters: 00:00 Intro', $first->summary);
        $this->assertSame('https://i2.ytimg.com/vi/abc123DEF45/hqdefault.jpg', $first->thumbnail);
        $this->assertSame('YouTube', $first->source);
        $this->assertTrue($first->external);

        $second = $items[1];
        $this->assertSame('Why we run a podcast', $second->title);
        $this->assertNull($second->summary);
        $this->assertSame('https://i2.ytimg.com/vi/xyz789GHI01/hqdefault.jpg', $second->thumbnail);
    }
}
