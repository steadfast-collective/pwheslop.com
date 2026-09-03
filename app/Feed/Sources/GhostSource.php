<?php

namespace App\Feed\Sources;

use App\Feed\FeedItem;
use Carbon\Carbon;

/**
 * Reads the RSS 2.0 feed a Ghost site publishes at /rss/.
 */
class GhostSource extends AbstractSource
{
    protected const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';
    protected const NS_MEDIA = 'http://search.yahoo.com/mrss/';

    public function parse(string $body): array
    {
        $xml = $this->loadXml($body);

        $items = [];

        foreach ($xml->channel->item ?? [] as $item) {
            $title = trim((string) $item->title);
            $url = trim((string) $item->link);
            $pubDate = trim((string) $item->pubDate);

            if ($title === '' || $url === '' || $pubDate === '') {
                continue;
            }

            $content = $item->children(self::NS_CONTENT);
            $media = $item->children(self::NS_MEDIA);

            $summary = trim((string) $item->description);
            if ($summary === '') {
                $summary = (string) $content->encoded;
            }

            $thumbnail = null;
            if (isset($media->content)) {
                $thumbnail = trim((string) $media->content->attributes()['url']) ?: null;
            }

            $items[] = new FeedItem(
                type: FeedItem::TYPE_ARTICLE,
                title: $title,
                url: $url,
                date: Carbon::parse($pubDate),
                summary: FeedItem::summarize($summary),
                thumbnail: $thumbnail,
                source: $this->label,
                external: true,
            );
        }

        return $items;
    }
}
