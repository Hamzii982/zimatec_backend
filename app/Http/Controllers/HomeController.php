<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected AiAssistantService $aiService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AiAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Statistics
        $stats = [
            'projects' => \App\Models\Project::count(),
            'machines' => \App\Models\Machine::count(),
            'users' => \App\Models\User::count(),
            'processes' => \DB::table('processes')->count(),
        ];

        $leistungen = [
            [
                'name' => 'Projekte',
                'description' => 'Alle unternehmensprojekte effizient Manage und Uberwachen.',
                'image' => 'images/cards/project card.png',
                'route' => 'projects',
            ],
            [
                'name' => 'Lagerverwaltung',
                'description' => 'Lager auswählen, Materialverbrauch verfolgen und Bestandsübersicht verwalten.',
                'image' => 'images/cards/hochregal card.webp',
                'route' => 'lager.select',       // ← updated
            ],
            [
                'name' => 'Zeiten Erfassung',
                'description' => 'Arbeitszeiten und Zeiterfassung der Mitarbeiter erfassen und verwalten.',
                'image' => 'images/cards/time card.jpg',
                'route' => 'time-records.list',
            ],
            [
                'name' => 'Druckprobleme',
                'description' => 'Melden und verwalten Sie technische Probleme an den Druckmaschinen.',
                'image' => 'images/cards/printing problems.avif',
                'route' => 'printer-problems.index',
            ],
            [
                'name' => 'Ressourcenplanung',
                'description' => 'Belegung der Maschinen planen und einsehen. Keine Anmeldung erforderlich.',
                'image' => 'images/cards/scheduler_card.png',
                'route' => 'scheduler.index',
            ],
            [
                'name' => 'Zimaboard',
                'description' => 'Unser Kommunikationskanal für persönliche Chats und Broadcast-Nachrichten an alle.',
                'image' => 'images/cards/zimaboard card.jpg',
                'url' => config('services.zimaboard.url'),
            ],
            [
                'name' => 'Zimatec AI',
                'description' => 'Interne Chatbots und KI-Agenten für alle KI-bezogenen Aufgaben.',
                'image' => 'images/cards/zimatec-ai card.jpg',
                'url' => config('services.zimatec_ai.url'),
            ],
        ];

        return view('user.home.index', compact('leistungen', 'stats'));
    }

    public function askRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'history' => 'nullable|array',
        ]);

        $userMessage = $request->input('message');
        $history = $request->input('history', []);

        // --- PHASE 1: ROUTING SCHEMA DEFINITION ---
        // This forces the LLM to decide if it's a general query or a specific database search.
        $routingSchema = [
            'type' => 'object',
            'properties' => [
                'is_project_query' => [
                    'type' => 'boolean',
                    'description' => 'True if the user is asking for status, updates, or details regarding specific projects, orders, or customers.',
                ],
                'search_parameters' => [
                    'type' => 'object',
                    'description' => 'Extraction of filters if is_project_query is true.',
                    'properties' => [
                        'project_name' => ['type' => 'string', 'description' => 'Project name keywords'],
                        'auftragsnummer_zf' => ['type' => 'string', 'description' => 'Order number starting with 3'],
                        'auftragsnummer_zt' => ['type' => 'string', 'description' => 'Order number starting with 4 or 2'],
                        'start_date' => ['type' => 'string', 'format' => 'date'],
                        'end_date' => ['type' => 'string', 'format' => 'date'],
                        'include_positions' => ['type' => 'boolean'],
                        'include_status' => ['type' => 'boolean'],
                        // --- NEW PARAMETERS FOR DYNAMIC MATCHING ---
                        'time_scope' => [
                            'type' => 'string',
                            'enum' => ['all', 'past', 'future_deadlines'],
                            'description' => "Filter relative to current time. 'past' for completed/older things. 'future_deadlines' if user asks for upcoming deadlines.",
                        ],
                        'sort_by' => [
                            'type' => 'string',
                            'enum' => ['created_at', 'end_time', 'start_time'],
                            'description' => "Which column to order by. Use 'end_time' if they care about deadlines.",
                        ],
                        'sort_direction' => [
                            'type' => 'string',
                            'enum' => ['asc', 'desc'],
                            'description' => "asc = oldest/earliest first. desc = newest/latest first. For 'first ever project', use created_at + asc.",
                        ],
                        // --- ADD THIS PARAMETER ---
                        'project_status' => [
                            'type' => 'string',
                            'enum' => ['neue', 'in_arbeit', 'abgeschlossen', 'storniert'], // Use your actual DB values
                            'description' => "The target status of the project. If the user asks for new projects by status, set this to 'neue'.",
                        ],
                        // --- ADD THIS PARAMETER ---
                        'project_positions' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'description' => 'The position name as in the database. If the user asks for projects by position, include an array of the relevant positions.',
                            ],
                            'description' => 'The target positions of the project. If the user asks for new projects by position, set this to include the desired positions.',
                        ],
                    ],
                ],
                'general_reply' => [
                    'type' => 'string',
                    'description' => 'If is_project_query is false, fulfill it here.',
                ],
            ],
            'required' => ['is_project_query'],
        ];

        $currentDate = now()->format('Y-m-d');

        $routerPrompt = <<<PROMPT
        Du bist der Routing-Assistent für das ZimaTec Arbeitsportal. Deine Aufgabe ist es zu analysieren, ob der Benutzer Informationen über Projekte abrufen möchte.
        HEUTE IST DER: $currentDate

        Verwende für die `search_parameters` Sortierungen folgende Logik:
        - "Erstes Projekt überhaupt" -> sort_by: "created_at", sort_direction: "asc"
        - "Neue / Letzte Projekte" -> sort_by: "created_at", sort_direction: "desc"
        - "Anstehende Deadlines / Projekte die bald enden" -> time_scope: "future_deadlines", sort_by: "end_time", sort_direction: "asc"
        - "Vergangene / Alte Projekte" -> time_scope: "past"

        Antworte ausnahmslos im vorgegebenen JSON-Format.
        PROMPT;

        try {
            // First Pass: Let the LLM evaluate the query intent
            $aiRouting = $this->aiService->askGeneralAssistant(
                $userMessage,
                $routerPrompt,
                '',
                $history,
                $routingSchema
            );

            $decision = $aiRouting['result'];

            // --- PHASE 2: HANDLE GENERAL ASSISTANCE (FALLBACK) ---
            if (! $decision || ! ($decision['is_project_query'] ?? false)) {
                // If it wasn't a project query, use the pre-generated general reply
                return response()->json([
                    'success' => true,
                    'reply' => $decision['general_reply'] ?? 'Entschuldigung, ich konnte die Anfrage nicht verarbeiten.',
                ]);
            }

            // --- PHASE 3: DYNAMIC PROJECT QUERY EXECUTION ---
            $params = $decision['search_parameters'] ?? [];

            // Start an Eloquent Query Builder instance
            $query = Project::query();

            if (! empty($params['project_name'])) {
                $query->where('project_name', 'LIKE', '%'.$params['project_name'].'%');
            }
            if (! empty($params['auftragsnummer_zf'])) {
                $query->where('auftragsnummer_zf', '=', $params['auftragsnummer_zf']);
            }
            if (! empty($params['auftragsnummer_zt'])) {
                $query->where('auftragsnummer_zt', '=', $params['auftragsnummer_zt']);
            }
            if (! empty($params['start_date'])) {
                $query->where('start_time', '>=', $params['start_date']);
            }
            if (! empty($params['end_date'])) {
                $query->where('end_time', '<=', $params['end_date']);
            }
            // --- ADD THIS STATUS FILTER ---
            if (! empty($params['project_status'])) {
                $query->whereHas('status', function ($q) use ($params) {
                    // Replace 'name' or 'slug' with the actual tracking column in your statuses table
                    $q->where('name', '=', $params['project_status']);
                });
            }

            // --- ADD THIS POSITIONS FILTER ---
            if (! empty($params['project_positions']) && is_array($params['project_positions'])) {
                $query->whereHas('positions', function ($q) use ($params) {
                    $q->whereIn('name', $params['project_positions']); // Replace 'name' with the actual column in your positions table
                });
            }

            // --- NEW: HANDLE TIME SCOPES ---
            if (! empty($params['time_scope'])) {
                if ($params['time_scope'] === 'future_deadlines') {
                    // end_time is in the future
                    $query->where('end_time', '>=', now());
                } elseif ($params['time_scope'] === 'past') {
                    // end_time has passed, or created a long time ago
                    $query->where('end_time', '<', now());
                }
            }

            // --- NEW: DYNAMIC SORTING HANDLING ---
            $sortBy = $params['sort_by'] ?? 'created_at'; // Default to created_at
            $sortDirection = $params['sort_direction'] ?? 'desc'; // Default to newest first

            $query->orderBy($sortBy, $sortDirection);

            // Eager load relationships
            $relations = [];
            if (! empty($params['include_positions']) && $params['include_positions'] === true) {
                $relations[] = 'positions';
            }
            if (! empty($params['include_status']) && $params['include_status'] === true) {
                $relations[] = 'status';
            }
            $query->with($relations);

            // Fetch matched records
            $projectsData = $query->limit(10)->get();

            if ($projectsData->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'reply' => 'Ich habe nach Projekten mit den angegebenen Kriterien gesucht, konnte aber leider keine passenden Einträge im System finden.',
                ]);
            }

            // --- PHASE 4: RE-SYNTHESIZE REAL DATA INTO REPLIES ---
            $systemPromptFinal = <<<'PROMPT'
            Du bist der ZimaTec Assistant, ein virtueller Support-Mitarbeiter für das zentrale Arbeitsportal.
            Beantworte alle Benutzeranfragen stets in deutscher Sprache, professionell, präzise und hilfsbereit.
            
            Dir stehen die echten, aktuellen Live-Projektdaten aus der Datenbank im Kontext zur Verfügung. 
            Analysiere diese Daten und beantworte die ursprüngliche Benutzerfrage basierend auf diesen Fakten.
            Nenne konkrete Details wie Projektnamen, Auftragsnummern oder Fristen, sofern im Kontext vorhanden.
            
            Regeln:
            - Antworte immer auf Deutsch.
            - Sei freundlich, aber geschäftsmäßig sachlich.
            - Nutze einfache Zeilenumbrüche für Lesbarkeit.
            PROMPT;

            // Package database result securely as clean context string
            $dbContextString = "Gefundene Projektdaten:\n".json_encode($projectsData->toArray(), JSON_PRETTY_PRINT);

            // Execute Second Pass without an output structure schema to get standard 'raw_text' Markdown formatting
            $finalAiResponse = $this->aiService->askGeneralAssistant(
                $userMessage,
                $systemPromptFinal,
                $dbContextString,
                $history
            );

            return response()->json([
                'success' => true,
                'reply' => $finalAiResponse['raw_text'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler bei der Verarbeitung Ihrer Anfrage: '.$e->getMessage(),
            ], 500);
        }

    }
}
