<?php

namespace Database\Seeders;

use App\Models\SalaryGroup;
use Illuminate\Database\Seeder;

/**
 * Seeds the EXACT 17 salary groups from Hreasy by WebSenor master.
 */
class SalaryGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [1,'PTPL - Staff','',8.33,'Progressive Tobacco Pvt. Ltd. EXPIRED',2,'EXPIRED'],
            [2,'PTPL - Company Labour','',8.33,'Progressive Tobacco Pvt. Ltd. EXPIRED',2,'EXPIRED'],
            [3,'Contarctor - Lal Singh Kitawat Expired','',8.33,'Progressive Tobacco Pvt. Ltd. EXPIRED',2,'EXPIRED'],
            [4,'Contarctor - Himmat Singh Kitawat','',8.33,'Progressive Tobacco Pvt. Ltd. EXPIRED',2,'EXPIRED'],
            [5,'PTPL - Maintainance','',8.33,'Progressive Tobacco Pvt. Ltd. EXPIRED',2,'EXPIRED'],
            [6,'BSK - Staff','',8.33,'BSK AGENCIES',3,'Active'],
            [7,'BSK - Company Labour','',8.33,'BSK AGENCIES',3,'Active'],
            [8,'Contractor - Nathu Singh','',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
            [9,'GTPPL - Staff','SB',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
            [1009,'GHPL - Staff','',8.33,'Galanan Hotels Pvt. Ltd.',4,'Active'],
            [1010,'PAEPL - Staff','',8.33,'Pinnacle Amusements and Events Pvt. Ltd.',5,'Active'],
            [1011,'Contractor - Lehar Singh Kitawat','',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
            [1012,'Contractor - Nathu Lal Vyas','',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
            [1013,'Contractor - Narayan Nath','',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
            [1014,'Contractor - Hari Singh Kitawat','',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
            [1015,'Contractor - Lal Singh Kitawat','',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
            [1016,'Contractor - Hukam Singh','',8.33,'Gyankeer Tobacco Products Private Limited',1,'Active'],
        ];

        foreach ($groups as [$id,$name,$type,$bonus,$company,$companyId,$status]) {
            SalaryGroup::create([
                'salary_group_id'   => $id,
                'salary_group_name' => $name,
                'group_type'        => $type ?: null,
                'bonus_per'         => $bonus,
                'under_company'     => $company,
                'company_id'        => $companyId,
                'status'            => $status,
                'pf_applicable'     => true,
                'esi_applicable'    => true,
                'pt_applicable'     => str_starts_with($company,'Gyankeer') || str_starts_with($company,'Progressive'),
                'lwf_applicable'    => str_starts_with($company,'Gyankeer') || str_starts_with($company,'Progressive'),
                'gratuity_applicable' => true,
                'effective_from'    => '2008-04-12',
            ]);
        }
    }
}
