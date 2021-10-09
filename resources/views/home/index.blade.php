@extends('youtube-api-views::template')

@section('content')
<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Convert</h5>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    
                    <form action="{{ route('youtube-api.submit') }}" method="post">
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
                        @if (config('youtube-api.auth') !== null)
                            <div class="form-floating mb-3">
                                <input class="form-control" type="text" name="token" placeholder="API token">
                                <label for="token">API token</label>
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

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Search</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('youtube-api.submit') }}" method="post">
                        @csrf

                        <div class="form-floating mb-3">
                            <input type="text" name="q" class="form-control" id="q" required placeholder="search term" />
                            <label for="q">Search term</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="number" name="max_results" id="max_results" class="form-control" value="10" placeholder="number">
                            <label for="max_results">Maximum results</label>
                        </div>
                        
                        @if (config('youtube-api.auth') !== null)
                            <div class="form-floating mb-3">
                                <input class="form-control" type="text" name="token" placeholder="API token">
                                <label for="token">API token</label>
                            </div>
                        @endif
                        
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i> Search</button>
                    </form>
                </div>
            </div>

            @if (session('searched'))
            <div class="card mt-3" id="search-response">
                <div class="card-header">
                    <h5 class="card-title">Json response</h5>
                </div>
                <div class="card-body">
                    <pre>@json(session('searched'), JSON_PRETTY_PRINT)</pre>
                </div>
                <div class="card-footer">
                    <table class="table table-borderless table-sm w-auto">
                        <tbody>
                            <tr>
                                <td>Error:</td>
                                <td>
                                    @if (session('searched')->error)
                                        <i class="fa fa-check" aria-hidden="true"></i>
                                    @else
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Error message:</td>
                                <td>{{ session('searched')->error ? session('searched')->message : '-'}}</td>
                            </tr>
                            <tr>
                                <td>Results:</td>
                                <td>
                                    <ul>
                                        @foreach (session('searched')->results as $result)
                                            <li>
                                                <a href="{{ $result->full_link }}">{{ $result->title }}</a>
                                                <button class="ms-3 btn btn-sm btn-outline-secondary" onclick="$('#url').val('{{ $result->full_link }}'); $(window).scrollTop(0); return false;">Convert</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection