<?php

namespace App\Services\AI\Tools;

use App\Services\AI\Contracts\AiToolContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuckDuckGoSearchTool implements AiToolContract
{
    private const API_URL = 'https://api.tavily.com/search';
    private function getApiKey(): ?string
    {
        return config('services.tavily.api_key');
    }

    public function name(): string
    {
        return 'duckduckgo_search';
    }

    public function description(): string
    {
        return 'Perform a web search using DuckDuckGo to get up-to-date real-time information, news, websites, and factual data from the internet.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query string to submit to DuckDuckGo.',
                ],
                'max_results' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of search results to return (1-10). Defaults to 5.',
                    'default' => 5,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function isAuthorized(?Authenticatable $user): bool
    {
        return true;
    }

    public function handle(array $arguments, ?Authenticatable $user = null): array
    {
        $query = trim($arguments['query'] ?? '');

        if (empty($query)) {
            return [
                'success' => false,
                'error' => 'Search query cannot be empty.',
                'results' => [],
            ];
        }

        $maxResults = min(max((int) ($arguments['max_results'] ?? 5), 1), 10);

        try {
            // Request Tavily API to get search results
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.self::getApiKey(),
            ])->post(self::API_URL, [
                'query' => $query,
                'search_depth' => 'basic',
                'max_results' => $maxResults,
            ]);

            if ($response->failed()) {
                Log::error('Tavily API error response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to reach Tavily search service.',
                    'results' => [],
                ];
            }

            // Extract results directly from Tavily's JSON response
            $results = collect($response->json('results', []))
                ->take($maxResults)
                ->map(fn ($item) => [
                    'title' => $item['title'] ?? '',
                    'url' => $item['url'] ?? '',
                    'snippet' => $item['content'] ?? '',
                ])
                ->all();

            return [
                'success' => true,
                'query' => $query,
                'total_results' => count($results),
                'results' => $results,
            ];

        } catch (\Throwable $e) {
            Log::error('Tavily search tool failed: '.$e->getMessage(), [
                'query' => $query,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while executing the search query.',
                'results' => [],
            ];
        }
    }
}