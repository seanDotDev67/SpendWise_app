<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionCookieProbe
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('__session-diagnostic')) {
            $hasSessionCookie = false;

            foreach ($response->headers->getCookies() as $cookie) {
                if ($cookie->getName() === config('session.cookie')) {
                    $hasSessionCookie = true;
                    break;
                }
            }

            $response->headers->set(
                'X-Session-Diagnostic-Cookie',
                $hasSessionCookie ? 'present' : 'absent'
            );
        }

        return $response;
    }
}
