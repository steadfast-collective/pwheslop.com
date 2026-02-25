<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Statamic\Exceptions\ValidationException;
use Statamic\Facades\Site;
use Symfony\Component\HttpFoundation\Response;

class ValidatePasswordResetUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->_reset_url) {
            $url = $request->_reset_url;

            $urlDomain = parse_url($url, PHP_URL_HOST);
            $currentRequestDomain = parse_url(url()->to('/'), PHP_URL_HOST);
            $isExternal = $urlDomain
                ? Site::all()
                    ->map(fn ($site) => parse_url($site->absoluteUrl(), PHP_URL_HOST))
                    ->push($currentRequestDomain)
                    ->filter(fn ($siteDomain) => ! is_null($siteDomain))
                    ->unique()
                    ->filter(fn ($siteDomain) => $siteDomain === $urlDomain)
                    ->isEmpty()
                : false;


            throw_if($isExternal, ValidationException::withMessages([
                '_reset_url' => trans('validation.url', ['attribute' => '_reset_url']),
            ]));
        }

        return $next($request);
    }
}
