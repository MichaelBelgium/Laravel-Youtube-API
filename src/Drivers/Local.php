<?php

namespace MichaelBelgium\YoutubeAPI\Drivers;

use Exception;
use Illuminate\Support\Facades\Storage;
use MichaelBelgium\YoutubeAPI\Models\Video;
use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;

class Local implements IDriver
{
    private YoutubeDl $youtubeDl;
    private Options $options;
    private string $format;

    public function __construct(string $url, string $format = 'mp3')
    {
        $this->youtubeDl = new YoutubeDl();
        $this->format = $format;

        $this->options = Options::create()
            ->noPlaylist()
            ->downloadPath(Video::getDownloadPath())
            ->proxy(config('youtube-api.proxy'))
            ->url($url);
    }

    public function convert(): Video
    {
        $options = $this->options->output('%(id)s.%(ext)s');

        if($this->format == 'mp3')
        {
            $options = $options->extractAudio(true)
                ->audioFormat('mp3')
                ->audioQuality('0');

            if(config('youtube-api.ffmpeg_path') !== null) {
                $options = $options->ffmpegLocation(config('youtube-api.ffmpeg_path'));
            }
        }
        else
            $options = $options->format('bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best');

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
        $ytdlVideo = $this->youtubeDl->download(
            $this->options->skipDownload(true)
        )->getVideos()[0];

        return $ytdlVideo;
    }
}