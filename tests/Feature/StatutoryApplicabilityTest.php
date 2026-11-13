<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeStatutoryChange;
use App\Models\SalaryGroup;
use App\Models\UserRole;
use App\Services\PFCalculator;
use App\Services\StatutoryApplicability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Effective-dated PF / EPS / ESI remove & restore (Employee > Statutory tab).
 */
class StatutoryApplicabilityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $emp;
    private UserRole $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['company_name' => 'Test Co', 'active_flag' => true]);
        $this->emp = Employee::create([
            'company_id'  => $this->company->company_id,
            'full_name'   => 'Suresh Meena',
            'active_flag' => true,
        ]);
        $this->user = UserRole::create([
            'username' => 'hr', 'login_email' => 'hr@test.local', 'password' => bcrypt('secret'),
        ]);
        $this->actingAs($this->user);
        session(['active_company_id' => $this->company->company_id]);
    }

    private function svc(): StatutoryApplicability
    {
        return new StatutoryApplicability();
    }

    public function test_no_history_falls_back_to_flag(): void
    {
        $this->assertTrue($this->svc()->isApplicable($this->emp, 'pf', '2026-11'));

        $this->emp->update(['esi_applicable_flag' => false]);
        $this->assertFalse($this->svc()->isApplicable($this->emp->fresh(), 'esi', '2026-11'));
    }

    public function test_remove_from_month_keeps_earlier_months(): void
    {
        $this->svc()->change($this->emp, 'pf', 'removed', '2026-12', 'Non-member', $this->user->user_id);

        $svc = $this->svc();
        $this->assertTrue($svc->isApplicable($this->emp, 'pf', '2026-11'));
        $this->assertFalse($svc->isApplicable($this->emp, 'pf', '2026-12'));
        $this->assertFalse($svc->isApplicable($this->emp, 'pf', '2027-03'));
    }

    public function test_restore_after_remove(): void
    {
        $this->svc()->change($this->emp, 'esi', 'removed', '2026-12', 'Wage > 21k', null);
        $this->svc()->change($this->emp, 'esi', 'restored', '2027-02', null, null);

        $svc = $this->svc();
        $this->assertTrue($svc->isApplicable($this->emp, 'esi', '2026-11'));
        $this->assertFalse($svc->isApplicable($this->emp, 'esi', '2027-01'));
        $this->assertTrue($svc->isApplicable($this->emp, 'esi', '2027-02'));
    }

    public function test_pf_removed_means_eps_off(): void
    {
        $this->svc()->change($this->emp, 'pf', 'removed', '2026-12', 'Non-member', null);

        $this->assertFalse($this->svc()->isApplicable($this->emp, 'eps', '2026-12'));
        $this->assertTrue($this->svc()->isApplicable($this->emp, 'eps', '2026-11'));
    }

    public function test_restore_when_legacy_flag_off_keeps_earlier_months_off(): void
    {
        $this->emp->update(['pf_applicable_flag' => false]);
        $emp = $this->emp->fresh();

        $this->svc()->change($emp, 'pf', 'restored', '2026-12', null, null);

        $svc = $this->svc();
        $this->assertFalse($svc->isApplicable($emp, 'pf', '2026-11'));
        $this->assertTrue($svc->isApplicable($emp, 'pf', '2026-12'));
    }

    public function test_current_month_change_syncs_flag_future_does_not(): void
    {
        $now = now()->format('Y-m');
        $this->svc()->change($this->emp, 'esi', 'removed', $now, 'x', null);
        $this->assertFalse((bool) $this->emp->fresh()->esi_applicable_flag);

        $future = now()->addMonths(2)->format('Y-m');
        $this->svc()->change($this->emp, 'pf', 'removed', $future, 'x', null);
        $this->assertTrue((bool) $this->emp->fresh()->pf_applicable_flag);
    }

    public function test_pf_calculator_without_eps(): void
    {
        $calc = app(PFCalculator::class);
        $with = $calc->compute(['basic' => 10000], '2026-11');
        $without = $calc->compute(['basic' => 10000], '2026-11', false, 0, 0, false);

        $this->assertSame(1200, $with['employee']);
        $this->assertSame(833, $with['eps']);
        $this->assertSame(367, $with['employer']);

        $this->assertSame(1200, $without['employee']);
        $this->assertSame(0, $without['eps']);
        $this->assertSame(1200, $without['employer']);
    }

    public function test_post_creates_history_and_updates_flag(): void
    {
        $now = now()->format('Y-m');
        $this->post("/employees/{$this->emp->emp_id}/statutory/applicability", [
            'scheme' => 'pf', 'action' => 'removed', 'effective_from' => $now, 'reason' => 'Form 11 non-member',
        ])->assertRedirect(route('employees.statutory', $this->emp->emp_id))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('employee_statutory_changes', [
            'emp_id' => $this->emp->emp_id, 'scheme' => 'pf', 'action' => 'removed',
            'reason' => 'Form 11 non-member', 'changed_by' => $this->user->user_id,
        ]);
        $this->assertFalse((bool) $this->emp->fresh()->pf_applicable_flag);
    }

    public function test_remove_requires_reason(): void
    {
        $this->post("/employees/{$this->emp->emp_id}/statutory/applicability", [
            'scheme' => 'esi', 'action' => 'removed', 'effective_from' => now()->format('Y-m'),
        ])->assertSessionHasErrors('reason');

        $this->assertSame(0, EmployeeStatutoryChange::count());
    }

    public function test_statutory_tab_renders(): void
    {
        $this->svc()->change($this->emp, 'esi', 'removed', now()->format('Y-m'), 'Wage > 21k', $this->user->user_id);

        $this->get("/employees/{$this->emp->emp_id}/statutory")
            ->assertOk()
            ->assertSee('PF / EPS / ESI Applicability')
            ->assertSee('Wage &gt; 21k', false);
    }

    public function test_new_employee_gets_salary_group_defaults(): void
    {
        $group = SalaryGroup::create([
            'company_id' => $this->company->company_id,
            'salary_group_name' => 'Contract Labour',
            'pf_applicable' => false,
            'esi_applicable' => true,
        ]);

        $this->post('/employees', [
            'first_name' => 'Mohan', 'company_id' => $this->company->company_id,
            'salary_group_id' => $group->salary_group_id,
        ]);

        $emp = Employee::where('first_name', 'Mohan')->firstOrFail();
        $this->assertFalse((bool) $emp->pf_applicable_flag);
        $this->assertTrue((bool) $emp->esi_applicable_flag);
    }
}
