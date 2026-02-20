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

        if ($user && $user->isTeacher()) {
            if (
                $request->routeIs('financial.index') ||
                $request->routeIs('invoices.index') ||
                $request->routeIs('invoices.show') ||
                $request->routeIs('invoices.generate') ||
                $request->routeIs('invoices.bulk-generate') ||
                $request->routeIs('invoices.print') ||
                $request->routeIs('invoices.send') ||
                $request->routeIs('invoices.send-receipt') ||
                $request->routeIs('invoices.send-pdf') ||
                $request->routeIs('invoices.pdf') ||
                $request->routeIs('invoices.recalculate') ||
                $request->routeIs('invoices.paid') ||
                $request->routeIs('invoices.unpaid') ||
                $request->routeIs('invoices.cancel')
            ) {
                return $next($request);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Você não tem permissão para acessar esta área financeira.');
        }

        if (!$user || !$user->canManageFinancial()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Você não tem permissão para acessar financeiro e despesas.');
        }

        return $next($request);
    }
}
