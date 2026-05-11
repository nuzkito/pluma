<?php

namespace App\Domain\Page;

use League\CommonMark\Extension\Embed\EmbedAdapterInterface;

class YoutubeNocookieEmbedAdapter implements EmbedAdapterInterface
{
    public function __construct(private EmbedAdapterInterface $adapter) {}

    public function updateEmbeds(array $embeds): void
    {
        $this->adapter->updateEmbeds($embeds);

        foreach ($embeds as $embed) {
            $code = $embed->getEmbedCode();

            if ($code !== null && str_contains($code, 'youtube.com')) {
                $embed->setEmbedCode(str_replace('youtube.com', 'youtube-nocookie.com', $code));
            }
        }
    }
}
