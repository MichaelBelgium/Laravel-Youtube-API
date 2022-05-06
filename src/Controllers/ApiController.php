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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use MichaelBelgium\YoutubeAPI\Models\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\ExecutableFinder;
use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;

class ApiController extends Controller
{
    const POSSIBLE_FORMATS = ['mp3', 'mp4'];

    public function convert(Request $request)
    {
        $ytRegex = '#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#';

        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string', 'url', 'regex:' . $ytRegex],
            'format' => [Rule::in(self::POSSIBLE_FORMATS)]
        ]);

        if($validator->fails()) {
            return response()->json(['error' => true, 'error_messages' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $validated = $validator->validated();

        $url = Arr::get($validated, 'url');
        $format = Arr::get($validated, 'format', 'mp3');
        preg_match($ytRegex, $url, $matches);

        $id = $matches[0];
        $maxLength = config('youtube-api.download_max_length', 0);
        $exists = File::exists(self::getDownloadPath($id.".".$format));
        //use yt-dlp if it's installed on the system, faster downloads and lot more improvements than youtube-dl
        $ytdlp = (new ExecutableFinder())->find('yt-dlp');

        if($maxLength > 0 || $exists)
        {
            try	{
                $dl = new YoutubeDl();

                if($ytdlp !== null)
                    $dl->setBinPath($ytdlp);

                $video = $dl->download(
                    Options::create()
                        ->noPlaylist()
                        ->skipDownload(true)
                        ->downloadPath(self::getDownloadPath())
                        ->url($url)
                )->getVideos()[0];
        
                if($video->getDuration() > $maxLength && $maxLength > 0)
                    return response()->json(['error' => true, 'message' => "The duration of the video is {$video->getDuration()} seconds while max video length is $maxLength seconds."], Response::HTTP_BAD_REQUEST);
            }
            catch (Exception $ex)
            {
                return response()->json(['error' => true, 'message' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        try
        {
            if($exists)
                $file = self::getDownloadUrl($id.".".$format);
            else
            {
                $options = Options::create()
                    ->noPlaylist()
                    ->downloadPath(self::getDownloadPath())
                    ->output('%(id)s.%(ext)s')
                    ->url($url);
    
                if($format == 'mp3')
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
                
                $dl = new YoutubeDl();

                if($ytdlp !== null)
                    $dl->setBinPath($ytdlp);

                $video = $dl->download($options)->getVideos()[0];

                if($video->getError() !== null)
                    throw new Exception($video->getError());

                $file = self::getDownloadUrl(File::basename($video->getFilename()));
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
            return response()->json(['error' => true, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
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

    public static function getDownloadPath(string $file = '')
    {
        return Storage::disk('public')->path($file);
    }

    public static function getDownloadUrl(string $file = '')
    {
        return Storage::disk('public')->url($file);
    }
}
