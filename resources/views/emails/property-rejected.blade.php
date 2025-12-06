@extends('emails.layout')

@section('content')
<h2>{{ __('emails.rejected_greeting', [], $locale) }}</h2>
<p>{{ __('emails.rejected_intro', ['title' => $property->title], $locale) }}</p>
<div class="warning-box">
    <p><strong>{{ __('emails.rejected_reason', [], $locale) }}:</strong> {{ $reason }}</p>
</div>
<p>{{ __('emails.rejected_next', [], $locale) }}</p>
<p style="text-align: center;">
    <a href="{{ $editUrl }}" class="button">{{ __('emails.rejected_button', [], $locale) }}</a>
</p>
@endsection
