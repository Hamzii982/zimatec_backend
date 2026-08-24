<?php
// app/Http/Controllers/AiChatController.php
namespace App\Http\Controllers;

use App\Services\AI\AiConversationOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            return response()->json(['success' => false, 'message' => 'Fehler: '.$e->getMessage()], 500);
        }
    }
}