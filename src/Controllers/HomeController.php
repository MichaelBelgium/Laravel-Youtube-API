<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('youtube-api-views::index');
    }
}
