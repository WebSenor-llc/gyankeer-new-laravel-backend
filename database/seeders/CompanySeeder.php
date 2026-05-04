<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code'=>'GTPPL','name'=>'Gyankeer Tobacco Products Private Limited','status'=>'Active',
             'cin'=>'U16001MH2008PTC182431','pan'=>'AAFCG2845K','tan'=>'MUMG12345E',
             'gstin'=>'27AAFCG2845K1Z9','epf_code'=>'MH/BAN/0123456/000','esic_code'=>'31000123456000999',
             'state'=>'Maharashtra','pin'=>'411019'],
            ['code'=>'PTPL','name'=>'Progressive Tobacco Pvt. Ltd.','status'=>'EXPIRED',
             'cin'=>'U16002MH1985PTC037512','pan'=>'AAACP4421C','tan'=>'MUMP00012H',
             'state'=>'Maharashtra','pin'=>'411001'],
            ['code'=>'BSK','name'=>'BSK Agencies','status'=>'Active',
             'cin'=>'U51505RJ1992PTC006841','pan'=>'AABCB1011L','tan'=>'JPRA00098A',
             'state'=>'Rajasthan','pin'=>'313001'],
            ['code'=>'GHPL','name'=>'Galanan Hotels Pvt. Ltd.','status'=>'Active',
             'cin'=>'U55101RJ1995PTC012108','pan'=>'AABCG7621N','tan'=>'JPRG00191B',
             'state'=>'Rajasthan','pin'=>'307501'],
            ['code'=>'PAEPL','name'=>'Pinnacle Amusements and Events Pvt. Ltd.','status'=>'Active',
             'cin'=>'U92419RJ2010PTC033124','pan'=>'AAECP9020M','tan'=>'JPRP00214C',
             'state'=>'Rajasthan','pin'=>'302017'],
            ['code'=>'OEPL','name'=>'Offspring Entertainment Pvt. Ltd.','status'=>'Active',
             'state'=>'Rajasthan','pin'=>'302001'],
            ['code'=>'SPPL','name'=>'Sansurum Provisions Pvt. Ltd.','status'=>'Active',
             'state'=>'Rajasthan','pin'=>'313001'],
        ];
        foreach ($rows as $r) {
            Company::create([
                'company_code'    => $r['code'],
                'company_name'    => $r['name'],
                'legal_name'      => $r['name'],
                'entity_type'     => 'Pvt Ltd',
                'status'          => $r['status'],
                'active_flag'     => $r['status'] === 'Active',
                'cin'             => $r['cin'] ?? null,
                'pan'             => $r['pan'] ?? null,
                'tan'             => $r['tan'] ?? null,
                'gstin'           => $r['gstin'] ?? null,
                'epf_establishment_code' => $r['epf_code'] ?? null,
                'esic_code'       => $r['esic_code'] ?? null,
                'state'           => $r['state'] ?? null,
                'pin_code'        => $r['pin'] ?? null,
                'country'         => 'India',
                'fy_start_month'  => 4,
                'base_currency'   => 'INR',
            ]);
        }
    }
}
