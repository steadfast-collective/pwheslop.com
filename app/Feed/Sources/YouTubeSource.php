<?php

namespace App\Feed\Sources;

use App\Feed\FeedItem;
use Carbon\Carbon;

/**
 * Reads the Atom feed YouTube publishes for a channel. It only ever contains
 * the latest 15 uploads, which is plenty for a homepage feed.
 */
class YouTubeSource extends AbstractSource
{
    protected const NS_ATOM = 'http://www.w3.org/2005/Atom';
    protected const NS_YT = 'http://www.youtube.com/xml/schemas/2015';
    protected const NS_MEDIA = 'http://search.yahoo.com/mrss/';

    public static function feedUrl(string $channelId): string
    {
        return 'https://www.youtube.com/feeds/videos.xml?channel_id='.$channelId;
    }

    public function parse(string $body): array
    {
        $xml = $this->loadXml($body);

        $items = [];

        foreach ($xml->children(self::NS_ATOM)->entry as $entry) {
            $atom = $entry->children(self::NS_ATOM);
            $yt = $entry->children(self::NS_YT);
            $media = $entry->children(self::NS_MEDIA);

            $videoId = trim((string) $yt->videoId);
            $title = trim((string) $atom->title);
            $published = trim((string) $atom->published);

            if ($videoId === '' || $title === '' || $published === '') {
                continue;
            }

            $url = 'https://www.youtube.com/watch?v='.$videoId;
            foreach ($atom->link as $link) {
                $attributes = $link->attributes();
                if ((string) $attributes['rel'] === 'alternate' && (string) $attributes['href'] !== '') {
                    $url = (string) $attributes['href'];
                    break;
                }
            }

            $thumbnail = 'https://i.ytimg.com/vi/'.$videoId.'/hqdefault.jpg';
            $description = null;

            if (isset($media->group)) {
                $group = $media->group->children(self::NS_MEDIA);

                if (isset($group->thumbnail)) {
                    $thumbnail = trim((string) $group->thumbnail->attributes()['url']) ?: $thumbnail;
                }

                if (isset($group->description)) {
                    $description = (string) $group->description;
                }
            }

            $items[] = new FeedItem(
                type: FeedItem::TYPE_VIDEO,
                title: $title,
                url: $url,
                date: Carbon::parse($published),
                summary: FeedItem::summarize($description),
                thumbnail: $thumbnail,
                source: $this->label,
                external: true,
            );
        }

        return $items;
    }
}
