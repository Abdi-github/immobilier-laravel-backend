@extends('emails.layout')

@section('content')
<h2>{{ __('emails.lead_greeting', [], $locale) }}</h2>
<p>{{ __('emails.lead_intro', [], $locale) }}</p>
<table class="details">
    @if($lead->property)
    <tr>
        <td>{{ __('emails.lead_property', [], $locale) }}</td>
        <td>{{ $lead->property->title }}</td>
    </tr>
    @endif
    <tr>
        <td>{{ __('emails.lead_from', [], $locale) }}</td>
        <td>{{ $lead->first_name }} {{ $lead->last_name }}</td>
    </tr>
    <tr>
        <td>{{ __('emails.lead_email', [], $locale) }}</td>
        <td>{{ $lead->email }}</td>
    </tr>
    @if($lead->phone)
    <tr>
        <td>{{ __('emails.lead_phone', [], $locale) }}</td>
        <td>{{ $lead->phone }}</td>
    </tr>
    @endif
    <tr>
        <td>{{ __('emails.lead_type', [], $locale) }}</td>
        <td>{{ ucfirst(str_replace('_', ' ', $lead->inquiry_type ?? '')) }}</td>
    </tr>
</table>
@if($lead->message)
<div class="info-box">
    <p><strong>{{ __('emails.lead_message_label', [], $locale) }}:</strong></p>
    <p>{{ $lead->message }}</p>
</div>
@endif
<p style="text-align: center;">
    <a href="{{ $leadUrl }}" class="button">{{ __('emails.lead_button', [], $locale) }}</a>
</p>
@endsection
