<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use MichaelBelgium\YoutubeAPI\Models\Log;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    public function index()
    {
        return view('youtube-api-views::home.index');
    }

    public function onPost(Request $request)
    {
        if($request->has('q'))
            $response = Http::get(route('youtube-api.search', $request->all()));
        else
            $response = Http::post(route('youtube-api.convert'), $request->all());

        if($response->status() == Response::HTTP_UNAUTHORIZED)
            return back()->with('error', $response->object()->message);
        else if($response->status() == Response::HTTP_BAD_REQUEST)
            return redirect()->back()->withErrors($response->object()->error_messages);
        
        return redirect()->back()->with($request->has('q') ? 'searched' : 'converted', $response->object());
    }

    public function logs(Request $request)
    {
        abort_if(config('youtube-api.enable_logging', false) === false, 404);

        $dates = Log::select(DB::raw('DATE(created_at) date'))->groupBy(DB::raw('DATE(created_at)'))->get();
        $logs = [];

        foreach ($dates as $date) {
            $logs[$date->date] = Log::where(DB::raw('DATE(created_at)'), $date->date)->get();
        }

        $keys = array_keys($logs);

        return view('youtube-api-views::logs.index', [
            'logs' => $logs,
            'firstDate' => reset($keys)
        ]);
    }
}
