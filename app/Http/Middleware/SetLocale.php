<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'fr', 'de', 'it'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang')
            ?? $request->header('Accept-Language')
            ?? config('app.locale', 'en');

        // Accept-Language header can be complex (e.g., "fr-CH,fr;q=0.9,en;q=0.8")
        // Extract the primary language code
        $locale = strtolower(substr((string) $locale, 0, 2));

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
