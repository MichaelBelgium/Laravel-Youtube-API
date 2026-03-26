<?php

namespace MichaelBelgium\YoutubeAPI;

use YoutubeDl\Options;

class CustomOptions extends Options
{
    private ?string $presetAlias = null;
    private ?string $jsRuntimes = null;

    public function presetAlias(?string $presetAlias): self
    {
        $new = clone $this;
        $new->presetAlias = $presetAlias;

        return $new;
    }

    public function jsRuntimes(?string $jsRuntimes): self
    {
        $new = clone $this;
        $new->jsRuntimes = $jsRuntimes;

        return $new;
    }

    public function toArray(): array
    {
        $arr = parent::toArray();

        $arr['preset-alias'] = $this->presetAlias;
        $arr['js-runtimes'] = $this->jsRuntimes;

        return $arr;
    }

    public static function create(): self
    {
        return new self();
    }
}