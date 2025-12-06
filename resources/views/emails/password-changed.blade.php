@extends('emails.layout')

@section('content')
<h2>{{ __('emails.changed_greeting', ['name' => $firstName], $locale) }}</h2>
<p>{{ __('emails.changed_intro', [], $locale) }}</p>
<div class="warning-box">
    <p>{{ __('emails.changed_warning', [], $locale) }}</p>
</div>
<p style="color: #888; font-size: 14px;">{{ __('emails.changed_time', ['time' => $changedAt], $locale) }}</p>
@endsection
