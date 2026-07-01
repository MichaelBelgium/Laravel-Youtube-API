<?php

namespace MichaelBelgium\YoutubeAPI\Drivers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MichaelBelgium\YoutubeAPI\Models\Video;

class CnvMp3 implements IDriver
{
    private const ENDPOINT = 'https://cnvmp3.com';
    private PendingRequest $request;
    private string $youtubeId;

    public function __construct(
        private readonly string $url,
        private readonly string $format = 'mp3'
    ) {
        $this->youtubeId = Video::getVideoId($this->url);

        $this->request = Http::withHeaders([
            'Origin' => self::ENDPOINT,
            'Referer' => self::ENDPOINT . '/v55',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36'
        ]);
    }

    public function convert(): Video
    {
        //Perhaps uses cobalt in the background; https://api.cnvmp3.com/

        $reqCheckDb = $this->request->post(self::ENDPOINT . '/check_database.php', [
            'youtube_id' => $this->youtubeId,
            'quality' => $this->format == 'mp4' ? 1000 : 0,
            'formatValue' => $this->format == 'mp4' ? 0 : 1
        ]);

        if (!$reqCheckDb->ok() || !$reqCheckDb->json('success'))
        {
            Log::debug('['. __CLASS__ .'] Video not found in database');
            Log::debug($reqCheckDb->json('error'));

            $reqVideoData = $this->request->post(self::ENDPOINT . '/get_video_data.php', [
                'token' => '1234',
                'url' => $this->url,
            ]);

            if (!$reqVideoData->ok() || !$reqVideoData->json('success'))
                throw new \Exception('Failed to get video data');

            $title = $reqVideoData->json('title');

            $reqVideoDownload = $this->request->post(self::ENDPOINT . '/download_video_ucep.php', [
                'quality' => $this->format == 'mp4' ? 1000 : 0,
                'formatValue' => $this->format == 'mp4' ? 0 : 1,
                'title' => $title,
                'url' => $this->url,
            ]);

            if (!$reqVideoDownload->ok() || !$reqVideoDownload->json('success'))
                throw new \Exception('Failed to get video download url');

            $stream = $reqVideoDownload->json('download_link');
        }
        else
        {
            $stream = $reqCheckDb->json('data.server_path');
            $title = $reqCheckDb->json('data.title');
        }

        if (!Storage::disk('public')->exists($this->youtubeId . '.' . $this->format))
        {
            // The stream url that's included in the response doesn't actually work, can't stream it directly so we have to download it and serve it ourselves
            $file = $this->request->withHeaders([
                'Referer' => self::ENDPOINT,
            ])->get($stream);

            $success = Storage::disk('public')
                ->put($this->youtubeId . '.' . $this->format, $file->body());

            if (!$success)
                throw new \Exception('Failed to save file');
        }

        $video = new Video(
            $this->youtubeId,
            $title,
            Video::getDownloadUrl($this->youtubeId . '.' . $this->format),
        );

        [$duration, $uploadedAt] = $this->getMetaData();
        $video->setDuration($duration);
        $video->setUploadedAt($uploadedAt);

        return $video;
    }

    // Todo: duplicate of function in Cobalt driver
    private function getMetaData(): array
    {
        if (env('GOOGLE_API_KEY') === null)
        {
            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze(Video::getDownloadPath($this->youtubeId . '.' . $this->format));

            return [
                $fileInfo['playtime_seconds'],
                null
            ];
        }
        else
        {
            //todo same as /info endpoint
            $gClient = new \Google_Client();
            $gClient->setDeveloperKey(env('GOOGLE_API_KEY'));

            $youtube = new \Google_Service_YouTube($gClient);
            $response = $youtube->videos->listVideos('snippet,contentDetails', ['id' => $this->youtubeId]);
            $ytVideo = $response->getItems()[0] ?? null;

            if ($ytVideo == null)
                throw new \Exception('Video not found');

            $duration = $ytVideo->getContentDetails()->getDuration();
            $interval = new \DateInterval($duration);
            $duration = ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;

            return [
                $duration,
                new \DateTime($ytVideo->getSnippet()->getPublishedAt())
            ];
        }
    }
}