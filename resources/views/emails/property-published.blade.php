@extends('emails.layout')

@section('content')
<h2>{{ __('emails.published_greeting', [], $locale) }}</h2>
<p>{{ __('emails.published_intro', ['title' => $property->title], $locale) }}</p>
<p style="text-align: center;">
    <a href="{{ $listingUrl }}" class="button">{{ __('emails.published_button', [], $locale) }}</a>
</p>
@endsection
