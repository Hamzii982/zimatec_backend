<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
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
}
