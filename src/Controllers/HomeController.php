<?php
namespace MichaelBelgium\YoutubeAPI\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            $response = Http::withToken($request->token)->get(route('youtube-api.search', $request->all()));
        else
            $response = Http::withToken($request->token)->post(route('youtube-api.convert'), $request->all());

        if($response->status() == Response::HTTP_UNAUTHORIZED || $response->status() == Response::HTTP_TOO_MANY_REQUESTS)
            return back()->with('error', $response->object()->message);
        else if($response->status() == Response::HTTP_BAD_REQUEST) {
            $errObj = $response->object();

            if(property_exists($errObj, 'error_messages'))
                return redirect()->back()->withErrors($errObj->error_messages);
            
            return back()->with('error', $errObj->message);
        }
        
        return redirect()->back()->with($request->has('q') ? 'searched' : 'converted', $response->object());
    }

    public function logs(Request $request)
    {
        abort_if(config('youtube-api.enable_logging', false) === false, Response::HTTP_NOT_FOUND);

        $selectedDate = $request->get('date');

        /** @var Collection $dates */
        $dates = Log::select(DB::raw('DATE(created_at) date'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'DESC')
            ->get();

        $dates = $dates->map(fn($date) => $date->date);

        $logs = Log::where(DB::raw('DATE(created_at)'), $request->get('date', $dates->first()))
            ->orderBy('created_at', 'DESC')
            ->simplePaginate(25)
            ->withQueryString();

        return view('youtube-api-views::logs.index', [
            'dates' => $dates,
            'logs' => $logs,
            'selectedDate' => $selectedDate,
        ]);
    }
}
