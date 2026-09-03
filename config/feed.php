<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Homepage Feed
    |--------------------------------------------------------------------------
    |
    | The homepage aggregates local posts with writing and videos published
    | elsewhere. Remote sources are fetched over HTTP and cached for the
    | number of seconds below. If a fetch fails, the last good result is
    | served for `failure_ttl` seconds before another attempt is made.
    |
    */

    'cache_ttl' => (int) env('FEED_CACHE_TTL', 3600),

    'failure_ttl' => (int) env('FEED_FAILURE_TTL', 300),

    'timeout' => (int) env('FEED_TIMEOUT', 5),

    'sources' => [

        'platforming_community' => [
            'label' => 'Platforming Community',
            'url' => env('FEED_PLATFORMING_COMMUNITY_URL', 'https://platformingcommunity.com/rss/'),
        ],

        'youtube' => [
            'label' => 'YouTube',
            'channel_id' => env('FEED_YOUTUBE_CHANNEL_ID', 'UC2wqdXFTQRDh2ZamjoH3NHQ'),
            // Optional: override the feed URL entirely (e.g. a playlist feed).
            'url' => env('FEED_YOUTUBE_URL'),
        ],

    ],

];
