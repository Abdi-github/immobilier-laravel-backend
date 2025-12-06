<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Immobilier.ch' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f7;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f7;
            padding: 30px 0;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background-color: #1a365d;
            padding: 24px 30px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .email-body h2 {
            font-size: 20px;
            color: #1a365d;
            margin-top: 0;
        }
        .email-body p {
            margin: 0 0 16px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 16px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .info-box {
            background-color: #f0f4ff;
            border-left: 4px solid #2563eb;
            padding: 16px;
            margin: 16px 0;
            border-radius: 0 4px 4px 0;
        }
        .warning-box {
            background-color: #fff8e1;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin: 16px 0;
            border-radius: 0 4px 4px 0;
        }
        .email-footer {
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            color: #888888;
            border-top: 1px solid #eeeeee;
        }
        .email-footer a {
            color: #2563eb;
            text-decoration: none;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        table.details td {
            padding: 8px 12px;
            border-bottom: 1px solid #eeeeee;
        }
        table.details td:first-child {
            font-weight: 600;
            color: #555555;
            width: 40%;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <h1>Immobilier.ch</h1>
            </div>
            <div class="email-body">
                @yield('content')
            </div>
            <div class="email-footer">
                <p>&copy; {{ date('Y') }} Immobilier.ch</p>
                @hasSection('unsubscribe')
                    <p>@yield('unsubscribe')</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
