<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use YoutubeDl\YoutubeDl;

class ApiController extends Controller
{
    const POSSIBLE_FORMATS = ['mp3', 'mp4'];

    public function convert(Request $request) {
        $url = $request->get('url');
        $format = $request->get('format', 'mp3');

        if(!in_array($format, self::POSSIBLE_FORMATS))
            return new JsonResponse(['error' => true, 'message' => 'Invalid format (choose between '. implode(', ', self::POSSIBLE_FORMATS). ')'], 422);

        $success = preg_match('#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#', $url, $matches);

        if(!$success)
            return new JsonResponse(['error' => true, 'message' => 'No video id specified'], 422);

        if(config('youtube-api') === null) {
            return new JsonResponse(['error' => true, 'message' => 'Please publish the config file by running \'php artisan vendor:publish --tag=youtube-api-config.\''], 422);
        }

        $id = $matches[0];
        $downloadFolder = config('youtube-api.download.path');
        $maxLength = config('youtube-api.download.max_length');
        $exists = File::exists($downloadFolder.$id.".".$format);

        if($maxLength > 0 || $exists)
        {
            $dl = new YoutubeDl(['skip-download' => true]);
            $dl->setDownloadPath($downloadFolder);
        
            try	{
                $video = $dl->download($url);
        
                if($video->getDuration() > $maxLength && $maxLength > 0)
                    return new JsonResponse(['error' => true, 'message' => "The duration of the video is {$video->getDuration()} seconds while max video length is $maxLength seconds."]);
            }
            catch (Exception $ex)
            {
                return new JsonResponse(['error' => true, 'message' => $ex->getMessage()]);
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
            $dl->setDownloadPath($downloadFolder);
        }

        try
        {
            $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/storage/";
            if($exists)
                $file = $fullUrl.$id.".".$format;
            else
            {
                $video = $dl->download($url);
                $file = $fullUrl.$video->getFilename();
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

    public function delete() {

    }

    public function search() {

    }
}
