@extends('admin.layout.admin-template')

@section('content')

    <div id="FileJobs">
        <h1>File Jobs</h1>

        <table class="table table-bordered table-sm table-hover table-inverse">
            <thead>
            <tr>
                <th>Original Name</th>
                <th>Error Message</th>
                <th>Encoding</th>
                <th>Type</th>
                <th>Input</th>
                <th>Output</th>
                <th>Finished at</th>
            </tr>
            </thead>
            <tbody>

            @foreach($fileJobs as $fileJob)
                <tr>
                    <td style="word-wrap:break-word;">{{ $fileJob->original_name }}</td>
                    <td>{{ __($fileJob->error_message) }}</td>
                    <td>{{ optional($fileJob->inputStoredFile->meta)->encoding }}</td>
                    <td>{{ substr(optional($fileJob->inputStoredFile->meta)->identified_as, strlen('App\Subtitles\PlainText\\')) }}</td>
                    <td><a target="_blank" href="{{ route('adminStoredFileDetail', ['id' => $fileJob->input_stored_file_id]) }}">{{ $fileJob->input_stored_file_id }}</a></td>
                    <td>
                        @if($fileJob->output_stored_file_id)
                            <a target="_blank" href="{{ route('adminStoredFileDetail', ['id' => $fileJob->output_stored_file_id]) }}">{{ $fileJob->output_stored_file_id }}</a>
                        @endif
                    </td>
                    <td>{{ $fileJob->finished_at }}</td>
                </tr>
            @endforeach

            </tbody>
        </table>
    </div>

@endsection