<?php

namespace App\Http\Controllers;

use App\Models\SalaryTransaction;
use Illuminate\Http\Request;

class SalaryTransactionController extends Controller
{
    public function index(Request $req)
    {
        $year  = (int) $req->input('year',  now()->year);
        $month = (int) $req->input('month', now()->month);

        $query = SalaryTransaction::where('period_year', $year)->where('period_month', $month);
        if ($req->filled('q')) {
            $q = $req->q;
            $query->where(function ($w) use ($q) {
                $w->where('employee_name', 'like', "%$q%")
                  ->orWhere('component_code', 'like', "%$q%")
                  ->orWhere('txn_type', 'like', "%$q%");
            });
        }
        $rows = $query->orderByDesc('txn_date')->paginate(50)->appends($req->query());

        $totals = [
            'debit'  => $rows->sum('debit_amount'),
            'credit' => $rows->sum('credit_amount'),
            'count'  => $rows->total(),
        ];

        return view('payroll.transactions', compact('rows', 'year', 'month', 'totals'));
    }
}
