@extends('youtube-api-views::template')

@section('styles')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <div class="container-fluid">
        <h1>Logs</h1>

        <select class="form-control my-4" name="log" onchange="window.location='/{{ config('youtube-api.route_prefix') }}/logs?date=' + this.value">
            @foreach ($dates as $date)
            <option value="<?= $date ?>" {{ $selectedDate == $date ? 'selected' : '' }}>
                {{ $date }}
            </option>
            @endforeach
        </select>


        <table class="table w-100">
            <thead>
                <tr>
                    <td>Time</td>
                    <td>ID</td>
                    <td>Title</td>
                    <td>Duration</td>
                    <td>Format</td>
                    <td>Location</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('H:i:s') }}</td>
                        <td>{{ $log->youtube_id }}</td>
                        <td>{{ $log->title }}</td>
                        <td>{{ $log->duration }} seconds</td>
                        <td>{{ $log->format }}</td>
                        <td>
                            @php
                                $path = \MichaelBelgium\YoutubeAPI\Models\Video::getDownloadPath($log->youtube_id.".".$log->format);
                            @endphp
                            @if (File::exists($path))
                                @php
                                    $url = \MichaelBelgium\YoutubeAPI\Models\Video::getDownloadUrl($log->youtube_id.".".$log->format);
                                @endphp
                                <a href="{{ $url }}" target="_blank">Converted file</a>
                            @else
                                Removed
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $logs->links() }}
    </div>
@endsection