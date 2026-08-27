<?php
// app/Http/Controllers/AiChatController.php
namespace App\Http\Controllers;

use App\Services\AI\AiConversationOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function ask(Request $request, AiConversationOrchestrator $orchestrator): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'nullable|string',
        ]);

        try {
            $result = $orchestrator->respond(
                $request->input('conversation_id'),
                $request->input('message'),
                $request->user()
            );

            return response()->json(['success' => true] + $result);
        } catch (\Exception $e) {
            Log::error('AI assistant error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
                'conversation_id' => $request->input('conversation_id'),
                'user_message' => $request->input('message'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Der Assistent ist derzeit nicht erreichbar. Bitte versuchen Sie es in ein paar Minuten erneut.',
            ], 500);
        }
    }
}