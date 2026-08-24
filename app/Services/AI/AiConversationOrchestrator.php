<?php
// app/Services/AI/AiConversationOrchestrator.php
namespace App\Services\AI;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\Authenticatable;

class AiConversationOrchestrator
{
    private const MAX_ROUNDS = 4;
    private const SESSION_PREFIX = 'ai_chat_history_';

    private const SYSTEM_PROMPT = <<<'PROMPT'
    Du bist der ZimaTec Assistant, ein virtueller Support-Mitarbeiter für das zentrale Arbeitsportal.
    Beantworte alle Benutzeranfragen stets in deutscher Sprache, professionell, präzise und hilfsbereit.

    Nutze die verfügbaren Tools, um dir fehlende Informationen zu beschaffen (z.B. Projektdaten,
    Druckerprobleme, E-Mail-Verläufe), bevor du antwortest. Erfinde niemals Details, die du nicht
    über ein Tool oder aus dem Gesprächsverlauf erhalten hast.

    Wenn du zu einem Druckerproblem Stellung nimmst, hole zuerst die Problemdetails über das
    passende Tool. Wenn du eine Herstellervail entwirfst oder überarbeitest, hole zuerst die
    Problemdetails und den bisherigen E-Mail-Verlauf.
    PROMPT;

    public function __construct(
        private AiAssistantClient $client,
        private AiToolRegistry $tools,
    ) {}

    public function respond(?string $conversationId, string $message, ?Authenticatable $user = null): array
    {
        $conversationId ??= (string) Str::uuid();
        $sessionKey = self::SESSION_PREFIX.$conversationId;

        $history = Session::get($sessionKey, []);

        $history[] = ['role' => 'human', 'content' => $message];

        $toolSchema = $this->tools->schema($user);

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            $response = $this->client->send(self::SYSTEM_PROMPT, $message, $history, $toolSchema);

            if (empty($response['tool_calls'])) {
                $history[] = ['role' => 'ai', 'content' => $response['text'] ?? ''];
                Session::put($sessionKey, $history);

                return [
                    'conversation_id' => $conversationId,
                    'reply' => $response['text'] ?? 'Entschuldigung, ich konnte die Anfrage nicht verarbeiten.',
                ];
            }

            // Record the AI's tool_calls turn.
            $history[] = [
                'role' => 'ai',
                'content' => '',
                'tool_calls' => $response['tool_calls'],
            ];

            // Execute each requested tool and record its result.
            foreach ($response['tool_calls'] as $toolCall) {
                $result = $this->tools->call($toolCall['name'], $toolCall['arguments'] ?? [], $user);
                $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);

                $history[] = [
                    'role' => 'tool',
                    'content' => $resultJson,
                    'tool_call_id' => $toolCall['id'],
                ];
            }
        }

        Session::put($sessionKey, $history);

        return [
            'conversation_id' => $conversationId,
            'reply' => 'Die Anfrage war zu komplex und konnte nicht abgeschlossen werden. Bitte versuche es erneut oder formuliere die Frage genauer.',
        ];
    }
}