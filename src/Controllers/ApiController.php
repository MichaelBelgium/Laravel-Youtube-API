<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use Exception;
use Google_Client;
use Google_Service_YouTube;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use MichaelBelgium\YoutubeAPI\Drivers\Cobalt;
use MichaelBelgium\YoutubeAPI\Drivers\Local;
use MichaelBelgium\YoutubeAPI\Models\Log;
use MichaelBelgium\YoutubeAPI\Models\Video;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string', 'url', 'regex:' . Video::URL_REGEX],
            'format' => [Rule::in(Video::POSSIBLE_FORMATS)]
        ]);

        if($validator->fails()) {
            return response()->json(['error' => true, 'error_messages' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $validated = $validator->validated();

        $url = Arr::get($validated, 'url');
        $format = Arr::get($validated, 'format', 'mp3');
        $id = Video::getVideoId($url);

        $lengthLimiter = config('youtube-api.videolength_limiter');
        $selectedDriver = config('youtube-api.driver', 'local');

        if($selectedDriver == 'local') {
            $driver = new Local($url, $format);
        } else if ($selectedDriver == 'cobalt') {
            $driver = new Cobalt($url, $format);
        } else {
            return response()->json(['error' => true, 'message' => 'Invalid driver'], Response::HTTP_BAD_REQUEST);
        }

        try
        {
            $video = $driver->convert();

            if($lengthLimiter !== null && is_callable($lengthLimiter))
            {
                $maxLength = $lengthLimiter($request);

                if($video->getDuration() > $maxLength && $maxLength > 0)
                    throw new Exception("The duration of the video is {$video->getDuration()} seconds while max video length is $maxLength seconds.");
            }

            if(config('youtube-api.enable_logging', false) === true)
            {
                $log = new Log();
                $log->youtube_id = $video->getId();
                $log->title = $video->getTitle();
                $log->duration = $video->getDuration();
                $log->format = $format;

                if(config('youtube-api.auth') !== null)
                    $log->user()->associate(Auth::user());

                $log->save();
            }

            return response()->json([
                'error' => false,
                ...$video->toArray()
            ]);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => true, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function delete(Request $request, string $id)
    {
        $removedFiles = [];

        foreach(Video::POSSIBLE_FORMATS as $format) {
            $localFile = Video::getDownloadPath("$id.$format");

            if(File::exists($localFile)) {
                File::delete($localFile);
                $removedFiles[] = $format;
            }
        }

        $resultNotRemoved = array_diff(Video::POSSIBLE_FORMATS, $removedFiles);

        if(empty($removedFiles))
            $message = 'No files removed.';
        else
            $message = 'Removed files: ' . implode(', ', $removedFiles) . '.';

        if(!empty($resultNotRemoved))
            $message .= ' Not removed: ' . implode(', ', $resultNotRemoved);

        return response()->json(['error' => false, 'message' => $message]);
    }

    public function search(Request $request)
    {
        if(empty(env('GOOGLE_API_KEY'))) {
            return response()->json(['error' => true, 'message' => 'No google api specified'], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'q' => ['required', 'string'],
            'max_results' => ['numeric']
        ]);


        if($validator->fails()) {
            return response()->json(['error' => true, 'error_messages' => $validator->errors()], Response::HTTP_BAD_REQUEST);
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

            return response()->json(['error' => false, 'message' => '', 'results' => $results]);
            
        } catch (Exception $ex) {
            $errorObj = json_decode($ex->getMessage());
            return response()->json(['error' => true, 'message' => $errorObj->error->message], Response::HTTP_BAD_REQUEST);
        }
    }


}
