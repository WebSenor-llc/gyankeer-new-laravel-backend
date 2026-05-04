<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Determines the "active company" for the current request.
 *
 * Reads `active_company_id` from session; if missing, defaults to the first
 * active company. Shares the active company id and the full company list with
 * every view (used by the layout header dropdown).
 *
 * Controllers can read the id via `session('active_company_id')` or the
 * `app('active_company_id')` singleton.
 */
class SetActiveCompany
{
    public function handle(Request $request, Closure $next)
    {
        $activeId = (int) session('active_company_id', 0);

        // Validate that the session id still exists (in case it was deleted)
        if (!$activeId || !Company::where('company_id', $activeId)->exists()) {
            $first = Company::where('active_flag', true)
                ->orderBy('company_id')
                ->first()
                ?? Company::orderBy('company_id')->first();
            $activeId = $first?->company_id ?? 0;
            session(['active_company_id' => $activeId]);
        }

        // Make available app-wide
        app()->instance('active_company_id', $activeId);

        // Share with all views (header dropdown + filters)
        View::share('activeCompanyId', $activeId);
        View::share('allCompanies', Company::orderBy('company_name')->get());

        return $next($request);
    }
}
