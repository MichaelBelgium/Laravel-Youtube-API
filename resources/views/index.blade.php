<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Youtube converter</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
</head>
<body>
    <div class="p-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Youtube converter</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('youtube-api.submit') }}" method="post" id="frm-convert">
                    @csrf
                    <div class="form-group">
                        <input type="text" name="url" class="form-control @error('url') is-invalid @enderror" id="url" placeholder="Youtube url" required value="{{ old('url') }}"/>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="format">Format</label>
                        <select class="form-control @error('format') is-invalid @enderror" name="format" id="format">
                            <option value="mp3">Audio (mp3)</option>
                            <option value="mp4">Video (mp4)</option>
                        </select>
                        @error('format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @if (config('youtube-api.enable_auth', false))
                        <div class="form-group">
                            <input class="form-control" type="text" name="api_token" placeholder="API token" required value="{{ request()->token }}">
                        </div>
                    @endif
                    <button type="submit" class="btn btn-outline-primary"><i class="fa fa-refresh" aria-hidden="true"></i> Convert</button>
                </form>
            </div>
        </div>

        @if (session('converted'))
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title">Json response</h5>
            </div>
            <div class="card-body">
                <pre>@json(session('converted'))</pre>
            </div>
            <div class="card-footer">
                <table class="table table-borderless table-sm w-auto">
                    <tbody>
                        <tr>
                            <td>Error:</td>
                            <td>
                                @if (session('converted')->error)
                                <i class="fa fa-check" aria-hidden="true"></i>
                                @else
                                <i class="fa fa-times" aria-hidden="true"></i>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Error message:</td>
                            <td>{{ session('converted')->error ? session('converted')->message : '-'}}</td>
                        </tr>
                        <tr>
                            <td>Title:</td>
                            <td>{{ session('converted')->title }} ({{ session('converted')->alt_title }})</td>
                        </tr>
                        <tr>
                            <td>Duration</td>
                            <td>{{ session('converted')->duration }} seconds</td>
                        </tr>
                        <tr>
                            <td>Youtube ID</td>
                            <td>{{ session('converted')->youtube_id }}</td>
                        </tr>
                        <tr>
                            <td>Uploaded at</td>
                            <td>{{ session('converted')->uploaded_at->date }}</td>
                        </tr>
                    </tbody>
                </table>
                <a target="_blank" class="btn btn-outline-primary" href="{{ session('converted')->file }}"><i class="fa fa-cloud-download" aria-hidden="true"></i> Listen/download</a>

                <form action="{{ route('youtube-api.delete', ['id' => session('converted')->youtube_id]) }}" method="post">
                    @method('DELETE')
                    @csrf
                    <button type="submit" class="btn btn-outline-danger"><i class="fa fa-trash" aria-hidden="true"></i> Remove file</button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>
</html>