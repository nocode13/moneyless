<?php

namespace App\Http\Middleware;

use App\Exceptions\EmailNotVerifiedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException();
        }

        return $next($request);
    }
}
