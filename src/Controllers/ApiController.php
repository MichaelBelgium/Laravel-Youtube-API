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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use MichaelBelgium\YoutubeAPI\Models\Log;
use Symfony\Component\HttpFoundation\Response;
use YoutubeDl\YoutubeDl;

class ApiController extends Controller
{
    const POSSIBLE_FORMATS = ['mp3', 'mp4'];

    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string', 'url', 'regex:#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#'],
            'format' => [Rule::in(self::POSSIBLE_FORMATS)]
        ]);

        if($validator->fails()) {
            return new JsonResponse(['error' => true, 'error_messages' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $validated = $validator->validated();

        $url = Arr::get($validated, 'url');
        $format = Arr::get($validated, 'format', 'mp3');

        parse_str(parse_url($url, PHP_URL_QUERY), $queryvars);

        $id = $queryvars['v'];
        $maxLength = config('youtube-api.download_max_length', 0);
        $exists = File::exists(self::getDownloadPath($id.".".$format));

        if($maxLength > 0 || $exists)
        {
            $dl = new YoutubeDl(['skip-download' => true]);
            $dl->setDownloadPath(self::getDownloadPath());
        
            try	{
                $video = $dl->download($url);
        
                if($video->getDuration() > $maxLength && $maxLength > 0)
                    return new JsonResponse(['error' => true, 'message' => "The duration of the video is {$video->getDuration()} seconds while max video length is $maxLength seconds."], Response::HTTP_BAD_REQUEST);
            }
            catch (Exception $ex)
            {
                return new JsonResponse(['error' => true, 'message' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
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
                );

                if(config('youtube-api.ffmpeg_path') !== null) {
                    $options['ffmpeg-location'] = config('youtube-api.ffmpeg_path');
                }
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
            $dl->setDownloadPath(self::getDownloadPath());
        }

        try
        {
            if($exists)
                $file = self::getDownloadUrl($id.".".$format);
            else
            {
                $video = $dl->download($url);
                $file = self::getDownloadUrl($video->getFilename());
            }

            if(config('youtube-api.enable_logging', false) === true) 
            {
                $log = new Log();
                $log->youtube_id = $video->getId();
                $log->title = $video->getTitle();
                $log->duration = $video->getDuration();
                $log->format = $format;
                //$log->user()->associate(Auth::user()) todo ?

                $log->save();
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
            $localFile = self::getDownloadPath($id.'.'.$format);

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

    public function search(Request $request)
    {
        if(empty(env('GOOGLE_API_KEY'))) {
            return new JsonResponse(['error' => true, 'message' => 'No google api specified'], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'q' => ['required', 'string'],
            'max_results' => ['numeric']
        ]);


        if($validator->fails()) {
            return new JsonResponse(['error' => true, 'error_messages' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $data = $validator->validated();
        
        $q = $data['q'];
        $max_results = $data['max_results'] ?? config('youtube-api.search_max_results', 10);

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

            return new JsonResponse(['error' => false, 'message' => '', 'results' => $results]);
            
        } catch (Exception $ex) {
            $errorObj = json_decode($ex->getMessage());
            return new JsonResponse(['error' => true, 'message' => $errorObj->error->message], Response::HTTP_BAD_REQUEST);
        }
    }

    public static function getDownloadPath(string $file = '')
    {
        return Storage::disk('public')->path($file);
    }

    public static function getDownloadUrl(string $file = '')
    {
        return Storage::disk('public')->url($file);
    }
}
