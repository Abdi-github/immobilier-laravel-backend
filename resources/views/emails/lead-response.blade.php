@extends('emails.layout')

@section('content')
<h2>{{ __('emails.response_greeting', ['name' => $recipientName], $locale) }}</h2>
<p>{{ __('emails.response_intro', ['property' => $lead->property?->title ?? ''], $locale) }}</p>
<p style="text-align: center;">
    <a href="{{ $responseUrl }}" class="button">{{ __('emails.response_button', [], $locale) }}</a>
</p>
@endsection
