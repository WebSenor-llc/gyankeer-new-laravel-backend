<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            // [code,name,type,calc_type,formula,pct_base,fixed,taxable,old_tax,new_tax,exempt,pf,esi,pt,gratuity,bonus,payslip,statutory,gl,seq]
            ['EARN-BASIC','Basic',                'Earning','Percentage','PCT_OF_GROSS',50,0,1,1,1,'',1,1,1,1,1,1,0,'5101-Basic',1],
            ['EARN-HRA','HRA',                    'Earning','Percentage','PCT_OF_GROSS',20,0,1,1,1,'§10(13A)',0,1,0,0,0,1,0,'5102-HRA',2],
            ['EARN-DA','Dearness Allowance',      'Earning','Percentage','PCT_OF_GROSS',10,0,1,1,1,'',1,1,1,1,1,1,0,'5103-DA',3],
            ['EARN-CONV','Conveyance',            'Earning','Fixed','FIXED',0,1600,1,1,1,'',0,1,0,0,0,1,0,'5104-Conv',4],
            ['EARN-MED','Medical Allowance',      'Earning','Fixed','FIXED',0,1250,1,1,1,'',0,1,0,0,0,1,0,'5105-Med',5],
            ['EARN-SPL','Special Allowance',      'Earning','Formula','GROSS-(BASIC+HRA+DA+CONV+MED)',0,0,1,1,1,'',0,1,0,0,0,1,0,'5106-Spl',6],
            ['EARN-LTA','LTA',                    'Earning','Annual','ANNUAL/12',0,0,1,1,1,'§10(5)',0,0,0,0,0,1,0,'5107-LTA',7],
            ['EARN-OT','Overtime',                'Earning','Formula','OT_HRS*(BASIC/26/8)*2',0,0,1,1,1,'',0,1,0,0,0,1,0,'5110-OT',10],
            ['EARN-INC','Incentive',              'Earning','Variable','PER_RUN',0,0,1,1,1,'',0,1,0,0,0,1,0,'5111-Inc',11],
            ['EARN-BON','Statutory Bonus',        'Earning','Annual','BONUS_ACT',8.33,0,1,1,1,'',0,0,0,0,0,1,1,'5112-Bonus',12],

            // Deductions
            ['DED-EPF','EPF (Employee 12%)',      'Deduction','Percentage','12%*MIN(BASIC+DA,15000)',12,0,0,0,0,'',0,0,0,0,0,1,1,'2120-EPF',20],
            ['DED-ESI','ESI (Employee 0.75%)',    'Deduction','Percentage','0.75%*GROSS_IF_LE_21K',0.75,0,0,0,0,'',0,0,0,0,0,1,1,'2125-ESI',22],
            ['DED-PT','Profession Tax',           'Deduction','Slab','STATE_SLAB',0,0,0,0,0,'',0,0,0,0,0,1,1,'2126-PT',23],
            ['DED-LWF','LWF',                     'Deduction','Fixed','STATE_FLAT',0,25,0,0,0,'',0,0,0,0,0,1,1,'2127-LWF',24],
            ['DED-TDS','TDS',                     'Deduction','Slab','ANNUAL/12',0,0,0,0,0,'',0,0,0,0,0,1,1,'2130-TDS',25],

            // Employer
            ['ER-EPF','Employer EPF (3.67%)',     'Employer','Percentage','3.67%*MIN(BASIC+DA,15000)',3.67,0,0,0,0,'',0,0,0,0,0,0,1,'2120-EPF',30],
            ['ER-EPS','EPS (8.33%)',              'Employer','Percentage','8.33%*MIN(BASIC+DA,15000)',8.33,0,0,0,0,'',0,0,0,0,0,0,1,'2122-EPS',31],
            ['ER-EDLI','EDLI (0.5%)',             'Employer','Percentage','0.5%*MIN(BASIC+DA,15000)',0.5,0,0,0,0,'',0,0,0,0,0,0,1,'2123-EDLI',32],
            ['ER-ADM','EPF Admin (0.5%)',         'Employer','Percentage','0.5%*MIN(BASIC+DA,15000)',0.5,0,0,0,0,'',0,0,0,0,0,0,1,'2124-Admin',33],
            ['ER-ESI','Employer ESI (3.25%)',     'Employer','Percentage','3.25%*GROSS_IF_LE_21K',3.25,0,0,0,0,'',0,0,0,0,0,0,1,'2125-ESI',34],
            ['ER-GRAT','Gratuity Provision',      'Employer','Percentage','4.81%*(BASIC+DA)',4.81,0,0,0,0,'',0,0,0,0,0,0,1,'2310-Grat',35],
            ['ER-LWF','LWF Employer',             'Employer','Fixed','SEMI_ANNUAL',0,75,0,0,0,'',0,0,0,0,0,0,1,'2127-LWF',36],
        ];

        foreach ($components as $c) {
            SalaryComponent::create([
                'component_code'    => $c[0],
                'component_name'    => $c[1],
                'component_type'    => $c[2],
                'calculation_type'  => $c[3],
                'formula'           => $c[4],
                'percentage_base'   => $c[5],
                'fixed_amount'      => $c[6],
                'is_taxable'        => $c[7],
                'taxable_under_old' => $c[8],
                'taxable_under_new' => $c[9],
                'exemption_section' => $c[10],
                'pf_wage'           => $c[11],
                'esi_wage'          => $c[12],
                'pt_wage'           => $c[13],
                'gratuity_wage'     => $c[14],
                'bonus_wage'        => $c[15],
                'show_on_payslip'   => $c[16],
                'statutory_flag'    => $c[17],
                'gl_account_code'   => $c[18],
                'sequence_order'    => $c[19],
                'effective_from'    => '2008-04-12',
                'status'            => 'Active',
            ]);
        }
    }
}
