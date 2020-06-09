<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('youtube-api-views::index');
    }

    public function onPost(Request $request)
    {
        $client = new Client();
        try {
            $response = $client->post(route('youtube-api.convert'), [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json'
                ],
                RequestOptions::FORM_PARAMS => $request->all()
            ]);

            return redirect()->back()->with('converted', json_decode($response->getBody()->getContents()));
        } catch (ClientException $ex) {
            $obj = json_decode($ex->getResponse()->getBody());
            if(property_exists($obj, 'error_messages'))
                return redirect()->back()->withErrors($obj->error_messages);
            else
                return redirect()->back()->with('error', $obj->message);
        }
    }
}
