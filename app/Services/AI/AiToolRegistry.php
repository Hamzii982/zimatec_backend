<?php
namespace App\Services\AI;

use App\Services\AI\Contracts\AiToolContract;
use Illuminate\Contracts\Auth\Authenticatable;

class AiToolRegistry
{
    /** @var AiToolContract[] */
    protected array $tools = [];

    public function register(AiToolContract $tool): static
    {
        $this->tools[$tool->name()] = $tool;
        return $this;
    }

    public function schema(?Authenticatable $user = null): array
    {
        return array_values(array_map(fn (AiToolContract $t) => [
            'type' => 'function',
            'function' => [
                'name' => $t->name(),
                'description' => $t->description(),
                'parameters' => $t->parameters(),
            ],
        ], array_filter($this->tools, fn (AiToolContract $t) => $t->isAuthorized($user))));
    }

    public function call(string $name, array $arguments, ?Authenticatable $user = null): array
    {
        if (!isset($this->tools[$name])) {
            return ['error' => "Unknown tool '{$name}'."];
        }

        if (!$this->tools[$name]->isAuthorized($user)) {
            return ['error' => "Access denied for tool '{$name}'."];
        }

        try {
            return $this->tools[$name]->handle($arguments, $user);
        } catch (\Throwable $e) {
            report($e);
            return ['error' => 'Tool execution failed.'];
        }
    }
}