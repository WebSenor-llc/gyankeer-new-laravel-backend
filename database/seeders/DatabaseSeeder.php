<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CompanySeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            BankSeeder::class,
            SalaryGroupSeeder::class,
            SalaryComponentSeeder::class,
            ShiftSeeder::class,
            LeaveTypeSeeder::class,
            HolidaySeeder::class,
            // Statutory rates (EPF/ESI/PT/LWF/TDS) editable from Settings UI
            StatutorySlabSeeder::class,
            // Real employee data imported from Excel uploads
            EmployeeImportSeeder::class,
            // FY 2025-26 increments + proposed salary breakdown (tabs 4-9 of increment Excel)
            IncrementImportSeeder::class,
            // Salary group rename (Staff/ST1/ST2/...) — disabled by request, keep original names
            // Demo data (optional — comment out for production)
            // DemoEmployeeSeeder::class,
        ]);
    }
}
