<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\SalaryGroupController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\SalaryRunController;
use App\Http\Controllers\PayslipController;
// (PayslipController is also used in the payroll group for runs/payslips)
use App\Http\Controllers\IncentiveController;
use App\Http\Controllers\ArrearController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\StatutoryController;
use App\Http\Controllers\AttendanceLeaveController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ESSController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Hreasy by WebSenor — Web Routes
|--------------------------------------------------------------------------
*/

// === Auth ===
Route::middleware('guest')->group(function() {
    Route::get ('/login',  [LoginController::class,'showLoginForm'])->name('login');
    Route::post('/login',  [LoginController::class,'login']);
});
Route::post('/logout', [LoginController::class,'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    // ============== Active company switcher ==============
    Route::post('/switch-company', function (\Illuminate\Http\Request $req) {
        $id = (int) $req->input('company_id');
        if ($id && \App\Models\Company::where('company_id', $id)->exists()) {
            session(['active_company_id' => $id]);
        }
        return back()->with('status', 'Company switched.');
    })->name('switch-company');

    // ============== Dashboard ==============
    Route::get('/',          [DashboardController::class,'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class,'index']);

    // ============== HR — Master Config ==============
    Route::resource('companies',   CompanyController::class);
    Route::resource('departments', DepartmentController::class);
    Route::resource('designations',DesignationController::class);

    // ============== Manage Employee (with 9 tabs) ==============
    Route::prefix('employees')->name('employees.')->controller(EmployeeController::class)->group(function() {
        Route::get   ('/',               'index')      ->name('index');
        Route::get   ('/create',         'create')     ->name('create');
        Route::post  ('/',               'store')      ->name('store');
        Route::get   ('/{empId}',        'show')       ->name('show');
        Route::get   ('/{empId}/edit',   'edit')       ->name('edit');
        Route::patch ('/{empId}',        'update')     ->name('update');
        Route::delete('/{empId}',        'destroy')    ->name('destroy');

        // Tabs
        Route::get('/{empId}/education',  'education') ->name('education');
        Route::get('/{empId}/employment', 'employment')->name('employment');
        Route::get('/{empId}/statutory',  'statutory') ->name('statutory');
        Route::get('/{empId}/bank',       'bank')      ->name('bank');
        Route::get('/{empId}/documents',  'documents') ->name('documents');
        Route::get('/{empId}/family',     'family')    ->name('family');
        Route::get('/{empId}/career',     'career')    ->name('career');
        Route::get('/{empId}/salary',     'salary')    ->name('salary');

        // Actions
        Route::post('/import',                  'bulkImport')   ->name('import');
        Route::post('/{empId}/initiate-exit',   'initiateExit') ->name('initiate-exit');
    });

    Route::get ('exit-employees',                   [EmployeeController::class,'exitList'])    ->name('exit-employees');
    Route::get ('exit-employees/pick',              [EmployeeController::class,'exitPicker'])  ->name('exit-employees.picker');
    Route::get ('exit-employees/{empId}/form',      [EmployeeController::class,'exitForm'])    ->name('exit-employees.form');
    Route::post('exit-employees/{empId}',           [EmployeeController::class,'exitStore'])   ->name('exit-employees.store');
    Route::post('exit-employees/{empId}/reactivate',[EmployeeController::class,'reactivate'])  ->name('exit-employees.reactivate');

    // ============== Payroll Config ==============
    Route::resource('salary-components', \App\Http\Controllers\SalaryComponentController::class);
    Route::resource('salary-groups',     SalaryGroupController::class);
    Route::resource('salary-structures', SalaryStructureController::class);
    Route::resource('banks',             BankController::class);

    // Salary Run / Generation / Register
    Route::prefix('payroll')->name('payroll.')->group(function () {
        // SUGAM-style salary generation: pick Company × Salary Group × Month
        Route::get  ('/generate',     [SalaryRunController::class,'generate'])   ->name('generate');
        Route::post ('/generate',     [SalaryRunController::class,'generateRun'])->name('generate.run');
        Route::post ('/generate/delete', [SalaryRunController::class,'deleteGroupPayroll'])->name('generate.delete');

        Route::get   ('/runs',                [SalaryRunController::class,'index'])->name('runs.index');
        Route::get   ('/runs/create',         [SalaryRunController::class,'create'])->name('runs.create');
        Route::post  ('/runs',                [SalaryRunController::class,'store'])->name('runs.store');
        Route::get   ('/runs/{runId}',        [SalaryRunController::class,'show'])->name('runs.show');
        Route::post  ('/runs/{runId}/compute',[SalaryRunController::class,'compute'])->name('runs.compute');
        Route::post  ('/runs/{runId}/approve',[SalaryRunController::class,'approve'])->name('runs.approve');
        Route::post  ('/runs/{runId}/post',   [SalaryRunController::class,'post'])->name('runs.post');
        Route::get   ('/runs/{runId}/bank-file',[SalaryRunController::class,'bankFile'])->name('runs.bank-file');

        // Payslips — list, view, and printable
        Route::get   ('/payslips',                                     [PayslipController::class,'index'])    ->name('payslips.index');
        Route::get   ('/payslips/{empId}/{year}/{month}',              [PayslipController::class,'show'])     ->name('payslips.show');
        Route::get   ('/payslips/{empId}/{year}/{month}/print',        [PayslipController::class,'printable'])->name('payslips.print');
    });

    Route::resource('incentives', IncentiveController::class);
    Route::resource('arrears',    ArrearController::class);
    Route::resource('loans',      LoanController::class);

    Route::get ('/payroll/post-deductions',          [\App\Http\Controllers\DeductionController::class,'index'])->name('post-deductions');
    Route::get ('/payroll/salary-deductions',          [\App\Http\Controllers\DeductionController::class,'listing'])->name('deductions.listing');
    Route::get ('/payroll/salary-deductions/create',   [\App\Http\Controllers\DeductionController::class,'create'])->name('deductions.create');
    Route::post('/payroll/salary-deductions',          [\App\Http\Controllers\DeductionController::class,'store'])->name('deductions.store');
    Route::get ('/payroll/salary-deductions/{empId}/edit', [\App\Http\Controllers\DeductionController::class,'edit'])->name('deductions.edit');
    Route::post('/payroll/salary-deductions/tds-quick', [\App\Http\Controllers\DeductionController::class,'updateTdsQuick'])->name('deductions.update-tds');

    Route::get ('/payroll/overtime-sheet',                [\App\Http\Controllers\OvertimeController::class,'sheet'])  ->name('overtime-sheet');
    Route::get ('/payroll/overtime-sheet/create',         [\App\Http\Controllers\OvertimeController::class,'create']) ->name('overtime-sheet.create');
    Route::post('/payroll/overtime-sheet',                [\App\Http\Controllers\OvertimeController::class,'store'])  ->name('overtime-sheet.store');
    Route::post('/payroll/overtime-sheet/{otId}/delete',  [\App\Http\Controllers\OvertimeController::class,'destroy'])->name('overtime-sheet.destroy');
    Route::get ('/payroll/emp-salary-config', [\App\Http\Controllers\SalaryStructureController::class,'configForm'])->name('emp-salary-config');

    // SUGAM-style "Manage Salary" — listing + per-employee salary configuration
    Route::get ('/payroll/manage-salary',                  [\App\Http\Controllers\SalaryStructureController::class,'index'])     ->name('manage-salary.index');
    Route::get ('/payroll/manage-salary/{empId}/config',   [\App\Http\Controllers\SalaryStructureController::class,'configForm'])->name('manage-salary.config');
    Route::post('/payroll/manage-salary/{empId}',          [\App\Http\Controllers\SalaryStructureController::class,'save'])      ->name('manage-salary.save');

    Route::get ('/payroll/transactions',   [\App\Http\Controllers\SalaryTransactionController::class,'index'])->name('payroll.transactions');
    Route::get ('/payroll/statistical',    [ReportController::class,'statistical'])->name('reports.statistical');

    // ============== Statutory & Compliance (India) ==============
    Route::prefix('statutory')->name('statutory.')->controller(StatutoryController::class)->group(function () {
        Route::get ('/pf-challan',           'pfChallan')    ->name('pf');
        Route::post('/pf-challan/generate',  'generateEcr')  ->name('pf.generate');
        Route::get ('/pf-challan/pdf',       'pfChallanPdf') ->name('pf.pdf');
        Route::get ('/esi-challan',          'esiChallan')   ->name('esi');
        Route::post('/esi-challan/generate', 'generateEsi')  ->name('esi.generate');
        Route::get ('/esi-challan/pdf',      'esiChallanPdf')->name('esi.pdf');
        Route::get ('/pt',                   'pt')           ->name('pt');
        Route::post('/pt/generate',          'generatePt')   ->name('pt.generate');
        Route::get ('/pt/pdf',               'ptPdf')        ->name('pt.pdf');
        Route::get ('/lwf',                  'lwf')          ->name('lwf');
        Route::post('/lwf/generate',         'generateLwf')  ->name('lwf.generate');
        Route::get ('/lwf/pdf',              'lwfPdf')       ->name('lwf.pdf');
        Route::get ('/tds',                  'tds')          ->name('tds');
        Route::post('/tds/generate',         'generateTds')  ->name('tds.generate');
        Route::get ('/tds/pdf',              'tdsPdf')       ->name('tds.pdf');
        Route::get ('/form24q',              'form24q')      ->name('form24q');
        Route::get ('/form16/{empId}',       'form16')       ->name('form16');
        Route::get ('/bonus',                'bonus')        ->name('bonus');
        Route::get ('/gratuity',             'gratuity')     ->name('gratuity');
        Route::get ('/posh',                 'posh')         ->name('posh');
        Route::get ('/calendar',             'calendar')     ->name('calendar');
    });

    // ============== Attendance & Leave ==============
    Route::prefix('attendance')->name('attendance.')->controller(AttendanceLeaveController::class)->group(function () {
        Route::get ('/daily',       'daily')        ->name('daily');
        Route::get ('/manual',      'manual')       ->name('manual');
        Route::post('/manual',      'bulkMark')     ->name('manual.mark');
        Route::get ('/grid',        'bulkGrid')     ->name('grid');
        Route::post('/grid',        'bulkGridSave') ->name('grid.save');
        Route::get ('/summary',     'summaryEntry') ->name('summary');
        Route::post('/summary',     'summaryEntrySave')->name('summary.save');
        Route::get ('/counts',          'counts')        ->name('counts');
        Route::post('/counts',          'countsSave')    ->name('counts.save');
        Route::get ('/counts-workers',  'countsWorkers') ->name('counts-workers');
        Route::post('/move-worker',     'moveWorker')    ->name('move-worker');
        Route::post('/set-reporting','setReportingSave')->name('set-reporting.save');
        Route::get ('/upload',      'uploadForm')   ->name('upload');
        Route::post('/upload',      'upload')       ->name('upload.post');
        Route::get ('/template',    'downloadTemplate')->name('upload.template');
        Route::get ('/set-reporting','setReporting')->name('set-reporting');
        Route::get ('/view-reporting','viewReporting')->name('view-reporting');
        Route::get ('/tour-od',     'tour')         ->name('tour');
    });

    Route::prefix('leave')->name('leave.')->controller(AttendanceLeaveController::class)->group(function () {
        Route::get ('/apply',     'leaveCreate')  ->name('apply');
        Route::post('/apply',     'leaveStore')   ->name('apply.store');
        Route::get ('/online',    'leaveOnline')  ->name('online');
        Route::post('/{id}/approve','leaveApprove')->name('approve');
        Route::post('/{id}/reject', 'leaveReject') ->name('reject');
        Route::get ('/balance',   'balance')      ->name('balance');
        Route::get ('/record',    'record')       ->name('record');
    });

    Route::resource('shifts',   ShiftController::class);
    Route::resource('holidays', HolidayController::class);

    // ============== Reports ==============
    Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::get('/salary-sheet',    'salarySheet')    ->name('salary-sheet');
        Route::get('/complete-salary', 'completeSalary') ->name('complete-salary');
        Route::get('/salary-slip',     'salarySlip')     ->name('salary-slip');
        Route::get('/salary-slip-pdf', 'salarySlipPDF')  ->name('salary-slip.pdf');
        Route::get('/hr-letters',      'hrLetters')      ->name('hr-letters');
        Route::get('/bank-sheet',      'bankSheet')      ->name('bank-sheet');
        Route::get('/increment',       'incrementReport')->name('increment');
        Route::get('/headcount',       'headcount')      ->name('headcount');
        Route::get('/exit',            'exit')           ->name('exit');
    });

    // ============== ESS (Self-service) ==============
    Route::prefix('ess')->name('ess.')->controller(ESSController::class)->group(function () {
        Route::get ('/',            'index')           ->name('index');
        Route::get ('/payslip',     'payslip')         ->name('payslip');
        Route::get ('/it-decl',     'itDeclaration')   ->name('it-decl');
        Route::post('/it-decl',     'saveItDeclaration')->name('it-decl.save');
        Route::get ('/form16',      'form16')          ->name('form16');
    });

    Route::resource('settings', SettingsController::class)->only(['index','update']);
});
