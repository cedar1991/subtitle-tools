@extends('base-template')

@section('title',       __('seo.title.error'))
@section('description', __('seo.description.error'))
@section('keywords',    __('seo.keywords.error'))

@section('content')

    @component('components.page-intro')

        @slot('title') Whoops @endslot

    @endcomponent

    <div class="container">

        <p>
            You uploaded one or more files that use a text encoding we don't support yet.
            <br/>
            <br/>
            The encoding you uploaded has been logged, we will try to support this encoding as soon as possible.
            <br/>
            <br/>
            Unfortunately, we can't process your file right now.
        </p>

        <br/>
        <br/>

        <a href="{{ route('home') }}" class="btn">Back to homepage</a>

    </div>

@endsection