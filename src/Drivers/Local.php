<?php

namespace MichaelBelgium\YoutubeAPI\Drivers;

use Exception;
use Illuminate\Support\Facades\Storage;
use MichaelBelgium\YoutubeAPI\CustomOptions;
use MichaelBelgium\YoutubeAPI\CustomProcessBuilder;
use MichaelBelgium\YoutubeAPI\Models\Video;
use YoutubeDl\YoutubeDl;

class Local implements IDriver
{
    private YoutubeDl $youtubeDl;
    private CustomOptions $options;
    private string $format;

    public function __construct(string $url, string $format = 'mp3')
    {
        $this->youtubeDl = new YoutubeDl(new CustomProcessBuilder());
        $this->youtubeDl->setBinPath(config('youtube-api.local.bin_path'));

        $this->format = $format;

        $this->options = CustomOptions::create()
            ->noPlaylist()
            ->downloadPath(Video::getDownloadPath())
            ->proxy(config('youtube-api.proxy'))
            ->cookies(
                Storage::disk('local')->exists('cookies.txt') ?
                Storage::disk('local')->path('cookies.txt') :
                null
            )->url($url);
    }

    public function convert(): Video
    {
        $options = $this->options->output('%(id)s.%(ext)s');

        if($this->format == 'mp3')
        {
            $options = $options->presetAlias('mp3')
                ->ffmpegLocation(config('youtube-api.local.ffmpeg_path'));
        }
        else
            $options = $options->presetAlias('mp4');

        if (config('youtube-api.local.js-runtime') !== null)
            $options = $options->jsRuntimes(config('youtube-api.local.js-runtime'));

        $id = Video::getVideoId($this->options->getUrl()[0]);

        if (Storage::disk('public')->exists($id . '.' . $this->format))
            $ytdlVideo = $this->getVideoWithoutDownload();
        else
            $ytdlVideo = $this->youtubeDl->download($options)->getVideos()[0];

        if ($ytdlVideo->getError() !== null)
            throw new Exception($ytdlVideo->getError());

        $video = new Video(
            $ytdlVideo->getId(),
            $ytdlVideo->getTitle(),
            Video::getDownloadUrl($ytdlVideo->getId() . '.' . $this->format)
        );

        $video->setUploadedAt($ytdlVideo->getUploadDate());
        $video->setDuration($ytdlVideo->getDuration());

        return $video;
    }

    public function getVideoWithoutDownload(): \YoutubeDl\Entity\Video
    {
        //todo kinda same as /info endpoint
        return $this->youtubeDl->download(
            $this->options->skipDownload(true)
        )->getVideos()[0];
    }
}