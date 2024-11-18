<?php

namespace MichaelBelgium\YoutubeAPI\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use MichaelBelgium\YoutubeAPI\Models\Video;

class Cobalt implements IDriver
{
    private string $url;
    private string $format;
    private \getID3 $getID3;

    public function __construct(string $url, string $format = 'mp3')
    {
        $this->getID3 = new \getID3();
        $this->url = $url;
        $this->format = $format;
    }

    public function convert(): Video
    {
        if (config('youtube-api.cobalt.url') === null)
            throw new Exception('Cobalt API url is not configured');

        $request = Http::accept('application/json');

        if (config('youtube-api.cobalt.auth') !== null)
            $request->withHeaders(['Authorization' => config('youtube-api.cobalt.auth')]);

        $response = $request->post(config('youtube-api.cobalt.url'), [
            'url' => $this->url,
            'downloadMode' => $this->format == 'mp3' ? 'audio' : 'auto',
            'youtubeHLS' => config('youtube-api.cobalt.hls', false)
        ]);

        if ($response->status() != 200)
            throw new Exception($response->body());

        $cobalt = $response->object();

        if ($cobalt->status != 'tunnel')
            throw new Exception('This status is not supported yet');

        $file = explode('_', $cobalt->filename);
        $cobalt->filename = $file[1] . '.' . $this->format;

        if (!Storage::disk('public')->exists($cobalt->filename))
        {
            $success = Storage::disk('public')
                ->put($cobalt->filename, file_get_contents($cobalt->url));

            if (!$success)
                throw new Exception('Failed to save file');
        }

        $fileInfo = $this->getID3->analyze(Video::getDownloadPath($cobalt->filename));

        $video = new Video(
            $file[1],
            $fileInfo['tags_html'][$this->format == 'mp3' ? 'id3v2' : 'quicktime']['title'][0],
            Video::getDownloadUrl($cobalt->filename)
        );

        $video->setDuration($fileInfo['playtime_seconds']);

        return $video;
    }

    public function getVideoInfo(): Video
    {
        $query = parse_url($this->url, PHP_URL_QUERY);
        parse_str($query, $query_params);
        $id = $query_params['v'];

        if (!Storage::disk('public')->exists($id . '.' . $this->format))
            return $this->convert();

        $fileInfo = $this->getID3->analyze(Video::getDownloadPath($id . '.' . $this->format));

        $video = new Video(
            $id,
            $fileInfo['tags_html'][$this->format == 'mp3' ? 'id3v2' : 'quicktime']['title'][0],
            Video::getDownloadUrl($id . '.' . $this->format)
        );

        $video->setDuration($fileInfo['playtime_seconds']);

        return $video;
    }
}