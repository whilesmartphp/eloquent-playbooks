<?php

namespace Tests\Support;

use Closure;
use Illuminate\Http\Request;

class ReadOnlyMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort(423, 'Playbooks are read-only here.');
    }
}
