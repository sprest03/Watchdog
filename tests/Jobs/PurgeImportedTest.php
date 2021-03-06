<?php

namespace DirectoryTree\Watchdog\Tests\Jobs;

use DirectoryTree\Watchdog\LdapScan;
use DirectoryTree\Watchdog\LdapScanEntry;
use DirectoryTree\Watchdog\Tests\TestCase;
use DirectoryTree\Watchdog\Jobs\PurgeImported;

class PurgeImportedTest extends TestCase
{
    public function test_job_deletes_all_scan_entries()
    {
        $scan = factory(LdapScan::class)->create();

        $entries = factory(LdapScanEntry::class)->times(10)->create([
            'scan_id' => $scan->id,
        ]);

        $this->assertCount(10, $entries);

        $scan = $entries->first()->scan;

        PurgeImported::dispatch($scan);

        $this->assertCount(0, $scan->entries()->get());
    }
}
