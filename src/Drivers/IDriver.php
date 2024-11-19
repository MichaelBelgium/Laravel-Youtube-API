<?php

namespace MichaelBelgium\YoutubeAPI\Drivers;

use MichaelBelgium\YoutubeAPI\Models\Video;

interface IDriver
{
    public function convert(): Video;
}