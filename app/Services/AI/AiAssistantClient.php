<?php
// app/Services/AI/AiAssistantClient.php
namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class AiAssistantClient
{
    private string $backendUrl;

    public function __construct()
    {
        $this->backendUrl = rtrim(config('services.ai_backend.url', env('AI_BACKEND_URL', 'http://127.0.0.1:8001')), '/');
    }

    public function send(string $systemPrompt, string $message, array $history, array $tools = []): array
    {
        $payload = [
            'system_prompt' => $systemPrompt,
            'message' => $message,
            'history' => $history,
        ];

        if ($tools) {
            $payload['tools'] = $tools;
        }

        $response = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->backendUrl}/ai/assist", $payload)
            ->throw()
            ->json();

        return [
            'text' => $response['raw_text'] ?? null,
            'tool_calls' => $response['tool_calls'] ?? null,
        ];
    }
}