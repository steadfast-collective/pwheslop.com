<?php

namespace App\Feed\Sources;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

abstract class AbstractSource implements Source
{
    public function __construct(
        protected string $key,
        protected string $label,
        protected string $url,
        protected int $timeout = 5,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function fetch(): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders(['User-Agent' => 'pwheslop.com feed (+https://pwheslop.com)'])
            ->get($this->url)
            ->throw();

        return $this->parse($response->body());
    }

    /**
     * Parse a raw feed body into items.
     *
     * @return \App\Feed\FeedItem[]
     */
    abstract public function parse(string $body): array;

    protected function loadXml(string $body): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);

            if ($xml === false) {
                $error = libxml_get_last_error();

                throw new RuntimeException(sprintf(
                    'Could not parse feed from %s: %s',
                    $this->url,
                    $error ? trim($error->message) : 'unknown XML error'
                ));
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
