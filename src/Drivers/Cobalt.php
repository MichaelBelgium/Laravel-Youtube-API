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

    public function __construct(string $url, string $format = 'mp3')
    {
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

        $id = Video::getVideoId($this->url);

        if (env('GOOGLE_API_KEY') === null)
        {
            $file = explode('_', $cobalt->filename);
            $cobalt->filename = $file[1] . '.' . $this->format;

            if (!Storage::disk('public')->exists($cobalt->filename))
            {
                $success = Storage::disk('public')
                    ->put($cobalt->filename, file_get_contents($cobalt->url));

                if (!$success)
                    throw new Exception('Failed to save file');
            }

            $url = Video::getDownloadUrl($cobalt->filename);
        }
        else
        {
            $url = $cobalt->url;
        }

        [$title, $duration, $uploadedAt] = $this->getMetaData();

        $video = new Video(
            $id,
            $title,
            $url
        );

        $video->setUploadedAt($uploadedAt);
        $video->setDuration($duration);

        return $video;
    }

    /**
     * @todo use some kind of cache, to prevent multiple requests or analyzes. The chances of the video changing title or (for sure) duration are ultra low
     */
    private function getMetaData(): array
    {
        $id = Video::getVideoId($this->url);

        if (env('GOOGLE_API_KEY') === null)
        {
            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze(Video::getDownloadPath($id . '.' . $this->format));

            return [
                $fileInfo['tags'][$this->format == 'mp3' ? 'id3v2' : 'quicktime']['title'][0],
                $fileInfo['playtime_seconds'],
                null
            ];
        }
        else
        {
            $gClient = new \Google_Client();
            $gClient->setDeveloperKey(env('GOOGLE_API_KEY'));

            $youtube = new \Google_Service_YouTube($gClient);
            $response = $youtube->videos->listVideos('snippet,contentDetails', ['id' => $id]);
            $ytVideo = $response->getItems()[0] ?? null;

            if ($ytVideo == null)
                throw new Exception('Video not found');

            $duration = $ytVideo->getContentDetails()->getDuration();
            $interval = new \DateInterval($duration);
            $duration = ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;

            return [
                $ytVideo->getSnippet()->getTitle(),
                $duration,
                new \DateTime($ytVideo->getSnippet()->getPublishedAt())
            ];
        }
    }
}