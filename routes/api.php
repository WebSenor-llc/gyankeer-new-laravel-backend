<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\PayslipApiController;
use App\Http\Controllers\Api\LeaveApiController;

/*
|--------------------------------------------------------------------------
| Hreasy by WebSenor — API Routes (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // === Employees ===
    Route::apiResource('employees', EmployeeApiController::class);
    Route::get('/employees/{empId}/payslips',   [PayslipApiController::class,'forEmployee']);
    Route::get('/employees/{empId}/leave-balance',[LeaveApiController::class,'balance']);

    // === Attendance ===
    Route::post('/attendance/punch',            [AttendanceApiController::class,'punch']);
    Route::post('/attendance/gps',              [AttendanceApiController::class,'gpsClockIn']);
    Route::get ('/attendance/today/{empId}',    [AttendanceApiController::class,'today']);

    // === Leave ===
    Route::apiResource('leave', LeaveApiController::class);

    // === ESS (mobile app) ===
    Route::get('/me',                 [EmployeeApiController::class,'me']);
    Route::get('/me/payslip/{period}',[PayslipApiController::class,'me']);
    Route::get('/me/form16/{fy}',     [PayslipApiController::class,'form16']);
});

// === Biometric / GPS device webhooks ===
Route::post('/webhooks/biometric/{deviceId}', [AttendanceApiController::class,'biometricWebhook']);
Route::post('/webhooks/gps',                  [AttendanceApiController::class,'gpsWebhook']);
