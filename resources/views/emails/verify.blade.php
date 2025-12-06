@extends('emails.layout')

@section('content')
<h2>{{ __('emails.verify_greeting', [], $locale) }}</h2>
<p>{{ __('emails.verify_intro', ['name' => $firstName], $locale) }}</p>
<p style="text-align: center;">
    <a href="{{ $verificationUrl }}" class="button">{{ __('emails.verify_button', [], $locale) }}</a>
</p>
<div class="info-box">
    <p>{{ __('emails.verify_expiry', ['hours' => $expiryHours], $locale) }}</p>
</div>
<p style="color: #888; font-size: 14px;">{{ __('emails.verify_ignore', [], $locale) }}</p>
@endsection
