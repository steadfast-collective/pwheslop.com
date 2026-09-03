<?php

namespace App\Feed;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * A single entry in the homepage feed, regardless of where it came from.
 */
class FeedItem
{
    public const TYPE_POST = 'post';
    public const TYPE_ARTICLE = 'article';
    public const TYPE_VIDEO = 'video';

    public function __construct(
        public string $type,
        public string $title,
        public string $url,
        public Carbon $date,
        public ?string $summary = null,
        public ?string $thumbnail = null,
        public ?string $source = null,
        public bool $external = false,
    ) {
    }

    /**
     * The array shape handed to Antlers (and stored in the cache).
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'url' => $this->url,
            'date' => $this->date,
            'summary' => $this->summary,
            'thumbnail' => $this->thumbnail,
            'source' => $this->source,
            'external' => $this->external,
            'is_post' => $this->type === self::TYPE_POST,
            'is_article' => $this->type === self::TYPE_ARTICLE,
            'is_video' => $this->type === self::TYPE_VIDEO,
        ];
    }

    /**
     * Turn a chunk of HTML (or plain text) into a short, single-line summary.
     */
    public static function summarize(?string $text, int $limit = 200): ?string
    {
        if ($text === null) {
            return null;
        }

        // Keep a space where block-level elements ended so paragraphs don't run together.
        $text = preg_replace('/<\/(?:p|div|h[1-6]|li|blockquote|tr)\s*>|<br\s*\/?>/i', ' ', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if ($text === '') {
            return null;
        }

        return Str::limit($text, $limit, '…');
    }
}
