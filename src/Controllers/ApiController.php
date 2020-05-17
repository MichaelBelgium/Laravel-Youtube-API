<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use Exception;
use Google_Client;
use Google_Service_YouTube;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use YoutubeDl\YoutubeDl;

class ApiController extends Controller
{
    const POSSIBLE_FORMATS = ['mp3', 'mp4'];

    public function convert(Request $request)
    {
        $url = $request->get('url');
        $format = $request->get('format', 'mp3');

        if(!in_array($format, self::POSSIBLE_FORMATS))
            return new JsonResponse(['error' => true, 'message' => 'Invalid format (choose between '. implode(', ', self::POSSIBLE_FORMATS). ')'], 422);

        $success = preg_match('#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#', $url, $matches);

        if(!$success)
            return new JsonResponse(['error' => true, 'message' => 'No video id specified'], 422);
        
        $id = $matches[0];
        $maxLength = config('youtube-api.download.max_length', 0);
        $exists = File::exists($this->getDownloadPath($id.".".$format));

        if($maxLength > 0 || $exists)
        {
            $dl = new YoutubeDl(['skip-download' => true]);
            $dl->setDownloadPath($this->getDownloadPath());
        
            try	{
                $video = $dl->download($url);
        
                if($video->getDuration() > $maxLength && $maxLength > 0)
                    return new JsonResponse(['error' => true, 'message' => "The duration of the video is {$video->getDuration()} seconds while max video length is $maxLength seconds."], 422);
            }
            catch (Exception $ex)
            {
                return new JsonResponse(['error' => true, 'message' => $ex->getMessage()], 422);
            }
        }

        if(!$exists)
        {
            if($format == 'mp3')
            {
                $options = array(
                    'extract-audio' => true,
                    'audio-format' => 'mp3',
                    'audio-quality' => 0,
                    'output' => '%(id)s.%(ext)s',
                    //'ffmpeg-location' => '/usr/local/bin/ffmpeg'
                );
            }
            else
            {
                $options = array(
                    'continue' => true,
                    'format' => 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best',
                    'output' => '%(id)s.%(ext)s'
                );
            }

            $dl = new YoutubeDl($options);
            $dl->setDownloadPath($this->getDownloadPath());
        }

        try
        {
            if($exists)
                $file = $this->getDownloadUrl($id.".".$format);
            else
            {
                $video = $dl->download($url);
                $file = $this->getDownloadUrl($video->getFilename());
            }

            return new JsonResponse([
                'error' => false,
                'youtube_id' => $video->getId(),
                'title' => $video->getTitle(),
                'alt_title' => $video->getAltTitle(),
                'duration' => $video->getDuration(),
                'file' => $file,
                'uploaded_at' => $video->getUploadDate()
            ]);
        }
        catch (Exception $e)
        {
            return new JsonResponse(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, string $id)
    {
        $removedFiles = [];

        foreach(self::POSSIBLE_FORMATS as $format) {
            $localFile = $this->getDownloadPath($id.'.'.$format);

            if(File::exists($localFile)) {
                File::delete($localFile);
                $removedFiles[] = $format;
            }
        }

        $resultNotRemoved = array_diff(self::POSSIBLE_FORMATS, $removedFiles);

        if(empty($removedFiles))
            $message = 'No files removed.';
        else
            $message = 'Removed files: ' . implode(', ', $removedFiles) . '.';

        if(!empty($resultNotRemoved))
            $message .= ' Not removed: ' . implode(', ', $resultNotRemoved);

        return new JsonResponse(['error' => false, 'message' => $message]);
    }

    public function search(Request $request, string $q)
    {
        if(empty(env('GOOGLE_API_KEY'))) {
            return new JsonResponse(['error' => true, 'message' => 'No google api specified'], 422);
        }

        $max_results = $request->get('max_results', config('youtube-api.search_max_results', 10));

        $gClient = new Google_Client();
        $gClient->setDeveloperKey(env('GOOGLE_API_KEY'));

        $guzzleClient = new Client([
            RequestOptions::HEADERS => [
                'referer' => env('APP_URL')
            ]
        ]);

        $gClient->setHttpClient($guzzleClient);

        $ytService = new Google_Service_YouTube($gClient);

        try {
            $search = $ytService->search->listSearch('id,snippet', [
                'q' => $q,
                'maxResults' => $max_results,
                'type' => 'video'
            ]);

            $results = [];

            foreach ($search['items'] as $searchResult)
            {
                $results[] = array(
                    'id' => $searchResult['id']['videoId'],
                    'channel' => $searchResult['snippet']['channelTitle'],
                    'title' => $searchResult['snippet']['title'],
                    'full_link' => 'https://youtube.com/watch?v='.$searchResult['id']['videoId']
                );
            }

            return new JsonResponse(['error' => false, 'message' => '', 'results' => $results], 422);
            
        } catch (Exception $ex) {
            $errorObj = json_decode($ex->getMessage());
            return new JsonResponse(['error' => true, 'message' => $errorObj->error->message], 422);
        }
    }

    private function getDownloadPath(string $file = '')
    {
        return Storage::disk('public')->path($file);
    }

    private function getDownloadUrl(string $file = '')
    {
        return Storage::disk('public')->url($file);
    }
}
