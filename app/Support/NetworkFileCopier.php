<?php

namespace App\Support;

/**
 * Wraps the raw file copy used to pull machine log files off the shared
 * network path, isolating filesystem/network access from the services
 * that use it.
 */
class NetworkFileCopier
{
    public function copy(string $source, string $destination): void
    {
        try {
            if (! copy($source, $destination)) {
                throw new \Exception("Failed to copy file from $source to $destination");
            }
        } catch (\Exception $e) {
            // NOTE: kept verbatim from the original — dd() halts the request
            // and the Log::error() below it never runs. Flagging this rather
            // than silently changing it; let me know if you'd like it fixed
            // to just log and continue instead.
            dd($e);
            \Log::error('Error copying network file: '.$e->getMessage());
        }
    }
}