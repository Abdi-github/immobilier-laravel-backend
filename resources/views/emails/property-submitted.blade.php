@extends('emails.layout')

@section('content')
<h2>{{ __('emails.submitted_greeting', [], $locale) }}</h2>
<p>{{ __('emails.submitted_intro', [], $locale) }}</p>
<table class="details">
    <tr>
        <td>{{ __('emails.submitted_title', [], $locale) }}</td>
        <td>{{ $property->title }}</td>
    </tr>
    @if($property->agency)
    <tr>
        <td>{{ __('emails.submitted_agency', [], $locale) }}</td>
        <td>{{ $property->agency->name }}</td>
    </tr>
    @endif
    <tr>
        <td>{{ __('emails.submitted_type', [], $locale) }}</td>
        <td>{{ ucfirst(str_replace('_', ' ', $property->property_type ?? '')) }}</td>
    </tr>
    @if($property->city)
    <tr>
        <td>{{ __('emails.submitted_location', [], $locale) }}</td>
        <td>{{ $property->city->name[$locale] ?? $property->city->name['en'] ?? '' }}</td>
    </tr>
    @endif
</table>
<p style="text-align: center;">
    <a href="{{ $reviewUrl }}" class="button">{{ __('emails.submitted_button', [], $locale) }}</a>
</p>
@endsection
