<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\UserRole;
use App\Services\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /leave/apply must refuse to grant leave the employee has no balance for.
 */
class LeaveApplyBalanceTest extends TestCase
{
    use RefreshDatabase;

    private Employee $emp;
    private LeaveType $cl;
    private int $fy;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['company_name' => 'Test Co', 'active_flag' => true]);
        $this->emp = Employee::create([
            'company_id'  => $company->company_id,
            'full_name'   => 'Ramesh Kumar',
            'active_flag' => true,
        ]);
        $this->cl = LeaveType::create([
            'leave_code' => 'CL', 'leave_name' => 'Casual Leave', 'active_flag' => true,
        ]);
        $this->fy = app(LeaveBalanceService::class)->fy();

        $this->actingAs(UserRole::create([
            'username' => 'hr', 'login_email' => 'hr@test.local', 'password' => bcrypt('secret'),
        ]));
        session(['active_company_id' => $company->company_id]);
    }

    private function apply(array $overrides = [])
    {
        return $this->post('/leave/apply', array_merge([
            'emp_id'        => $this->emp->emp_id,
            'leave_type_id' => $this->cl->leave_type_id,
            'from_date'     => now()->toDateString(),
            'to_date'       => now()->toDateString(),
        ], $overrides));
    }

    private function balance(string $closing, string $availed = '0'): void
    {
        LeaveBalance::create([
            'emp_id'          => $this->emp->emp_id,
            'fy'              => $this->fy,
            'leave_code'      => 'CL',
            'leave_type_id'   => $this->cl->leave_type_id,
            'opening_balance' => $closing,
            'availed_ytd'     => $availed,
            'closing_balance' => $closing,
            'active_flag'     => true,
        ]);
    }

    public function test_blocks_when_no_balance_row_exists(): void
    {
        $this->apply()->assertSessionHasErrors('leave_type_id');
        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_blocks_when_balance_is_exhausted(): void
    {
        $this->balance('0');
        $this->apply()->assertSessionHasErrors('leave_type_id');
        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_allows_within_balance(): void
    {
        $this->balance('3');
        $this->apply()->assertSessionHasNoErrors();
        $this->assertSame(1, LeaveApplication::count());
    }

    public function test_blocks_more_days_than_available(): void
    {
        $this->balance('1');
        $this->apply(['to_date' => now()->addDays(2)->toDateString()])
            ->assertSessionHasErrors('leave_type_id');
        $this->assertSame(0, LeaveApplication::count());
    }

    /** Applications not yet consumed into leave_ledger must still reduce availability. */
    public function test_earlier_application_reduces_availability_before_payroll(): void
    {
        $this->balance('2');
        $this->apply()->assertSessionHasNoErrors();

        // 2 available, 1 already applied and unledgered → only 1 left, ask for 2.
        $this->apply([
            'from_date' => now()->addDays(5)->toDateString(),
            'to_date'   => now()->addDays(6)->toDateString(),
        ])->assertSessionHasErrors('leave_type_id');

        $this->assertSame(1, LeaveApplication::count());
    }

    /** Ledgered consumption is already inside closing_balance — don't subtract it twice. */
    public function test_ledgered_consumption_is_not_double_counted(): void
    {
        // Allotted 5, payroll consumed 4 → closing 1, availed_ytd 4.
        $this->balance('1', '4');
        LeaveApplication::create([
            'emp_id'          => $this->emp->emp_id,
            'leave_code'      => 'CL',
            'leave_type_id'   => $this->cl->leave_type_id,
            'from_date'       => now()->subDays(30)->toDateString(),
            'to_date'         => now()->subDays(27)->toDateString(),
            'days'            => 4,
            'approval_status' => 'Approved',
            'active_flag'     => true,
        ]);

        $this->assertSame(1.0, app(LeaveBalanceService::class)->available($this->emp->emp_id, 'CL'));
        $this->apply()->assertSessionHasNoErrors();
    }

    public function test_half_day_needs_only_half_a_day(): void
    {
        $this->balance('0.5');
        $this->apply(['half_day_flag' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, LeaveApplication::count());
    }
}
