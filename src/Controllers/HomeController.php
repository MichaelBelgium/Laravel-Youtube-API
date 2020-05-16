<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    public function index()
    {
        return view('youtube-api-views::index');
    }

    public function onPost(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'url', 'regex:#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#'],
            'format' => ['required', Rule::in(ApiController::POSSIBLE_FORMATS)]
        ]);

        $client = new Client();
        try {
            $response = $client->post(route('youtube-api.convert'), [
                RequestOptions::FORM_PARAMS => $data
            ]);

            return redirect()->back()->with('converted', json_decode($response->getBody()->getContents()));
        } catch (ClientException $ex) {

        }
    }
}
