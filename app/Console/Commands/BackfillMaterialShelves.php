<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Shelf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillMaterialShelves extends Command
{
    /**
     * php artisan materials:backfill-shelves
     * php artisan materials:backfill-shelves --dry-run
     */
    protected $signature = 'materials:backfill-shelves {--dry-run}';

    protected $description = 'Create Shelf records from existing materials.tablar values and link materials.shelf_id to them';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Running in DRY-RUN mode, no changes will be saved.' : 'Running backfill...');

        // Distinct (lager_id, tablar) combos that still need a shelf
        $combos = Material::query()
            ->whereNotNull('tablar')
            ->where('tablar', '!=', '')
            ->whereNull('shelf_id')
            ->select('lager_id', 'tablar')
            ->distinct()
            ->get();

        $this->info("Found {$combos->count()} distinct (lager_id, tablar) combinations to migrate.");

        $created = 0;
        $linked = 0;

        DB::beginTransaction();

        try {
            foreach ($combos as $combo) {
                $tablarName = trim($combo->tablar);

                $shelf = Shelf::firstOrCreate(
                    [
                        'lager_id' => $combo->lager_id,
                        'name' => $tablarName,
                    ],
                    [
                        'is_active' => true,
                    ]
                );

                if ($shelf->wasRecentlyCreated) {
                    $created++;
                }

                $affected = Material::query()
                    ->where('lager_id', $combo->lager_id)
                    ->where('tablar', $combo->tablar)
                    ->whereNull('shelf_id')
                    ->update(['shelf_id' => $shelf->id]);

                $linked += $affected;
            }

            // Sanity check: anything left unlinked that still has a tablar value?
            $remaining = Material::whereNotNull('tablar')
                ->where('tablar', '!=', '')
                ->whereNull('shelf_id')
                ->count();

            $this->info("Shelves created: {$created}");
            $this->info("Materials linked: {$linked}");
            $this->info("Materials still unlinked (should be 0): {$remaining}");

            if ($remaining > 0) {
                throw new \RuntimeException("{$remaining} materials could not be linked to a shelf. Rolling back.");
            }

            if ($dryRun) {
                $this->warn('Dry run — rolling back transaction, nothing was saved.');
                DB::rollBack();
            } else {
                DB::commit();
                $this->info('Backfill committed successfully.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Shelf backfill failed: '.$e->getMessage());
            $this->error('Backfill failed and was rolled back: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}