<?php

namespace App\Http\Controllers;

use App\Models\StatutorySlab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index(Request $req)
    {
        $fy = (int) $req->input('fy', 2025);

        $rates = StatutorySlab::where('category', 'rate')
            ->where('fy_start_year', $fy)
            ->orderBy('key')->get()
            ->groupBy(fn ($r) => explode('.', $r->key)[0]);

        $pt  = StatutorySlab::where('category', 'pt')->where('fy_start_year', $fy)->orderBy('key')->get();
        $lwf = StatutorySlab::where('category', 'lwf')->where('fy_start_year', $fy)->orderBy('key')->get();
        $tds = StatutorySlab::where('category', 'tds')->where('fy_start_year', $fy)->orderBy('key')->get();

        return view('settings.index', compact('fy', 'rates', 'pt', 'lwf', 'tds'));
    }

    public function update(Request $req, $id = null)
    {
        $values  = $req->input('value', []);
        $updated = 0;
        foreach ($values as $rowId => $val) {
            if ($val === null || $val === '') continue;
            $slab = StatutorySlab::find($rowId);
            if ($slab) {
                $slab->update(['value_decimal' => (float) $val]);
                Cache::forget("stat_rate:{$slab->key}:{$slab->fy_start_year}");
                $updated++;
            }
        }
        return back()->with('status', "Updated {$updated} statutory rates. Recompute any Draft/Computed payroll runs to apply.");
    }
}
