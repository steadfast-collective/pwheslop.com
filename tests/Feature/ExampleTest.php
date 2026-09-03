<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_homepage_renders_a_merged_feed()
    {
        Http::fake([
            'platformingcommunity.com/*' => Http::response(file_get_contents(__DIR__.'/../Fixtures/ghost-rss.xml')),
            'www.youtube.com/*' => Http::response(file_get_contents(__DIR__.'/../Fixtures/youtube-atom.xml')),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);

        // A local post.
        $response->assertSee('Steadfast Collective X Lobo Creative');

        // A Platforming Community article, linking off-site.
        $response->assertSee('Flywheels and motion.');
        $response->assertSee('href="https://platformingcommunity.com/flywheels-and-motion/" target="_blank" rel="noopener"', false);
        $response->assertSee('Platforming Community &nearr;', false);

        // A YouTube video with its thumbnail.
        $response->assertSee('Building community platforms with Laravel');
        $response->assertSee('https://i2.ytimg.com/vi/abc123DEF45/hqdefault.jpg');
        $response->assertSee('YouTube &nearr;', false);
    }

    public function test_the_homepage_still_renders_when_remote_feeds_are_down()
    {
        Http::fake(fn () => Http::response('', 500));

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Steadfast Collective X Lobo Creative');
        $response->assertDontSee('Flywheels and motion.');
    }
}
