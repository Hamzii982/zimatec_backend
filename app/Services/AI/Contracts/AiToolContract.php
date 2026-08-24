<?php
namespace App\Services\AI\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AiToolContract
{
    public function name(): string;
    public function description(): string;
    public function parameters(): array; // JSON schema, just this tool's fields
    public function handle(array $arguments): array; // returns data for synthesis

    public function isAuthorized(?Authenticatable $user): bool;
}