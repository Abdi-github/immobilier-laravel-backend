@extends('emails.layout')

@section('content')
<h2>{{ __('emails.reset_greeting', ['name' => $firstName], $locale) }}</h2>
<p>{{ __('emails.reset_intro', [], $locale) }}</p>
<p style="text-align: center;">
    <a href="{{ $resetUrl }}" class="button">{{ __('emails.reset_button', [], $locale) }}</a>
</p>
<div class="info-box">
    <p>{{ __('emails.reset_expiry', ['minutes' => $expiryMinutes], $locale) }}</p>
</div>
<p style="color: #888; font-size: 14px;">{{ __('emails.reset_ignore', [], $locale) }}</p>
@endsection
