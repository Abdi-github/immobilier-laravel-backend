@extends('emails.layout')

@section('content')
<h2>{{ __('emails.approved_greeting', [], $locale) }}</h2>
<p>{{ __('emails.approved_intro', ['title' => $property->title], $locale) }}</p>
<p style="text-align: center;">
    <a href="{{ $propertyUrl }}" class="button">{{ __('emails.approved_button', [], $locale) }}</a>
</p>
@endsection
