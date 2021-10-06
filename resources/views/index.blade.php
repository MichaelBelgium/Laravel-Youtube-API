<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Youtube converter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body>
    <div class="p-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Convert</h5>
            </div>
            <div class="card-body">
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                
                <form action="{{ route('youtube-api.submit') }}" method="post" id="frm-convert">
                    @csrf
                    <div class="form-floating mb-3">
                        <input type="text" name="url" class="form-control @error('url') is-invalid @enderror" id="url" placeholder="Youtube url" required value="{{ old('url') }}"/>
                        <label for="url">Youtube url</label>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-floating mb-3">
                        <select class="form-control @error('format') is-invalid @enderror" name="format" id="format">
                            <option value="mp3">Audio (mp3)</option>
                            <option value="mp4">Video (mp4)</option>
                        </select>
                        <label for="format">Format</label>
                        @error('format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @if (config('youtube-api.enable_auth', false))
                        <div class="form-group">
                            <input class="form-control" type="text" name="api_token" placeholder="API token" required value="{{ request()->token }}">
                        </div>
                    @endif
                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-sync-alt"></i> Convert</button>
                </form>
            </div>
        </div>

        @if (session('converted'))
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title">Json response</h5>
            </div>
            <div class="card-body">
                <pre>@json(session('converted'), JSON_PRETTY_PRINT)</pre>
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
                        <tr>
                            <td>
                                <a target="_blank" class="btn btn-outline-primary" href="{{ session('converted')->file }}"><i class="fas fa-download"></i> Listen/download</a>
                                <form action="{{ route('youtube-api.delete', ['id' => session('converted')->youtube_id]) }}" method="post" class="d-inline-block">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger"><i class="fa fa-trash" aria-hidden="true"></i> Remove file</button>
                                </form>
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>
</html>