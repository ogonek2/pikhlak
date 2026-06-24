@extends('admin.layouts.app')
@section('title', $client->full_name)
@section('content')
<div id="client-profile-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/client-profile.js')
    <script>
        window.__CLIENT_PROFILE__ = @json($profilePayload);
    </script>
@endpush
