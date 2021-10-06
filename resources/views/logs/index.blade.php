@extends('youtube-api-views::template')

@section('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.10.25/datatables.min.css"/>
@endsection

@section('content')
    <div class="container-fluid">
        <h1>Logs</h1>

        <select class="form-control my-4" name="log" onchange="onLogSelect();">
            @foreach ($logs as $date => $list)
            <option value="<?= $date ?>">
                {{ $date }}
            </option>
            @endforeach
        </select>

        @foreach ($logs as $date => $list)
        <div class="result {{ $firstDate == $date ? '' : 'd-none' }}" id="<?= $date ?>">
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
                    @foreach ($list as $log)
                        <tr>
                            <td>{{ $log->created_at->format('H:i:s') }}</td>
                            <td>{{ $log->youtube_id }}</td>
                            <td>{{ $log->title }}</td>
                            <td>{{ $log->duration }} seconds</td>
                            <td>{{ $log->format }}</td>
                            <td>
                                @php
                                    $path = MichaelBelgium\YoutubeAPI\Controllers\ApiController::getDownloadPath($log->youtube_id.".".$log->format);
                                @endphp
                                @if (File::exists($path))
                                    @php
                                        $url = MichaelBelgium\YoutubeAPI\Controllers\ApiController::getDownloadUrl($log->youtube_id.".".$log->format);
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
        </div>
        @endforeach
    </div>
@endsection

@section('scripts')
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.10.25/datatables.min.js"></script>

    <script>
        $(document).ready(function () {
            $('table').DataTable({ pageLength: 25 });
        });

        function onLogSelect() {
            var selected = $('select[name=log] option:selected').val();

            $('.result').each(function(index, el) {
                $(el).addClass('d-none');
            });

            $('#' + selected).removeClass('d-none');
        }
    </script>
@endsection