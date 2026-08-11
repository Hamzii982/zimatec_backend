<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

it('logs a heartbeat when test:scheduler runs', function () {
    Log::spy();

    $exitCode = Artisan::call('test:scheduler');

    expect($exitCode)->toBe(0);
    Log::shouldHaveReceived('info')->once();
});

it('writes a heartbeat line to the scheduler test log', function () {
    $logPath = storage_path('logs/scheduler_test.log');

    // Ensure a clean state so we can assert exactly one new line.
    if (file_exists($logPath)) {
        unlink($logPath);
    }

    $exitCode = Artisan::call('test:scheduler');

    expect($exitCode)->toBe(0);
    expect(file_exists($logPath))->toBeTrue();

    $contents = file_get_contents($logPath);
    expect($contents)->toContain('Scheduler ran at');
});
