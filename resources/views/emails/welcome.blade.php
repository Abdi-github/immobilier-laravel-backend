@extends('emails.layout')

@section('content')
<h2>{{ __('emails.welcome_greeting', ['name' => $firstName], $locale) }}</h2>
<p>{{ __('emails.welcome_intro', [], $locale) }}</p>
<p>{{ __('emails.welcome_features', [], $locale) }}</p>
<ul>
    <li>{{ __('emails.welcome_feature_search', [], $locale) }}</li>
    <li>{{ __('emails.welcome_feature_inquire', [], $locale) }}</li>
    <li>{{ __('emails.welcome_feature_alerts', [], $locale) }}</li>
</ul>
<p style="text-align: center;">
    <a href="{{ $dashboardUrl }}" class="button">{{ __('emails.welcome_button', [], $locale) }}</a>
</p>
@endsection
