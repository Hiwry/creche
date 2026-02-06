<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinancialAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->canManageFinancial()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Você não tem permissão para acessar financeiro e despesas.');
        }

        return $next($request);
    }
}
