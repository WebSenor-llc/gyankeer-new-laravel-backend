<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('statutory_slabs', 'effective_from')) {
            Schema::table('statutory_slabs', function (Blueprint $t) {
                $t->date('effective_from')->nullable()->after('fy_start_year');
                $t->date('effective_to')->nullable()->after('effective_from');
                $t->index(['category', 'key', 'effective_from'], 'ix_stat_slab_eff');
            });

            // Backfill from fy_start_year → effective_from = <fy>-04-01
            DB::table('statutory_slabs')
                ->whereNotNull('fy_start_year')
                ->whereNull('effective_from')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $r) {
                        DB::table('statutory_slabs')->where('id', $r->id)->update([
                            'effective_from' => sprintf('%04d-04-01', (int) $r->fy_start_year),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('statutory_slabs', 'effective_from')) {
            Schema::table('statutory_slabs', function (Blueprint $t) {
                $t->dropIndex('ix_stat_slab_eff');
                $t->dropColumn(['effective_from', 'effective_to']);
            });
        }
    }
};
