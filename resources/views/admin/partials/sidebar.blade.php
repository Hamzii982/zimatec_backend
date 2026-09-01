<div class="sidebar d-flex flex-column p-3">
    <a href="{{ route('home') }}" class="d-flex align-items-center justify-content-center mb-3">
        {{-- <a href="{{ route('home') }}"> --}}
            <img src="{{ asset('images/zimmermann-logo-192.png') }}" alt="Zimatec Logo"
                class="img-fluid me-2" style="width: 40px; height: 40px;">
            <h5 class="fw-bold mb-0">ZiMaTec</h5>
        {{-- </a> --}}
        </a>
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </a>
    
    {{-- Users with submenu --}}
    @if(config('modules.teams'))
        @php
            $usersActive = request()->is('admin/users*');
        @endphp
        <a data-bs-toggle="collapse" href="#usersSubmenu" role="button"
        aria-expanded="{{ $usersActive ? 'true' : 'false' }}"
        aria-controls="usersSubmenu"
        class="{{ $usersActive ? 'active' : '' }}">
            <i class="bi bi-people me-2"></i> Team
        </a>
        <div class="collapse submenu {{ $usersActive ? 'show' : '' }}" id="usersSubmenu">
            <a href="{{ route('admin.users') }}" class="{{ request()->is('admin/users') ? 'active' : '' }}">Alle Benutzer</a>
        </div>
    @endif

    {{-- Project offers with submenu --}}
    @if(config('modules.project_offers'))
        @php
            $projectOffersActive = request()->is('admin/project_offers*');
        @endphp
        <a data-bs-toggle="collapse" href="#projectOffersSubmenu" role="button"
        aria-expanded="{{ $projectOffersActive ? 'true' : 'false' }}"
        aria-controls="projectsSubmenu"
        class="{{ $projectOffersActive ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text me-2"></i> AngebotManagement
        </a>
        <div class="collapse submenu {{ $projectOffersActive ? 'show' : '' }}" id="projectOffersSubmenu">
            <a href="{{ route('admin.project_offers.index') }}" class="{{ request()->is('admin/project_offers*') ? 'active' : '' }}">Alle ProjektAngebote</a>
        </div>
    @endif

    {{-- Projects with submenu --}}
    @if(config('modules.projects'))
        @php
            $projectsActive = request()->is('admin/projects*');
            $bauteileActive = request()->is('admin/bauteile*');
        @endphp
        <a data-bs-toggle="collapse" href="#projectsSubmenu" role="button"
        aria-expanded="{{ $projectsActive || $bauteileActive ? 'true' : 'false' }}"
        aria-controls="projectsSubmenu"
        class="{{ $projectsActive || $bauteileActive ? 'active' : '' }}">
            <i class="bi bi-kanban me-2"></i> Projektmanagement
        </a>
        <div class="collapse submenu {{ $projectsActive || $bauteileActive ? 'show' : '' }}" id="projectsSubmenu">
            <a href="{{ route('admin.projects') }}" class="{{ request()->is('admin/projects') ? 'active' : '' }}">Alle Projekten</a>
            <a href="{{ route('admin.bauteile.index') }}" class="{{ request()->is('admin/bauteile*') ? 'active' : '' }}">Alle Bauteilen</a>
            <a href="{{ route('admin.projects.projects.index') }}" class="{{ request()->is('admin/projects/projects*') ? 'active' : '' }}">Alle Fertigungsprozesse</a>
            <a href="{{ route('admin.projects.offers') }}" class="{{ request()->is('admin/projects/offer*') ? 'active' : '' }}">Alle Lieferantenangebote</a>
            <a href="{{ route('admin.workflow.settings') }}" class="{{ request()->is('admin/workflow*') ? 'active' : '' }}">Workflow-Einstellungen
            </a>
        </div>
    @endif

    {{-- Time Records with submenu --}}
    @if(config('modules.time'))
        @php
            $timeActive = request()->is('admin/time*');
        @endphp
        <a data-bs-toggle="collapse" href="#timeSubmenu" role="button"
        aria-expanded="{{ $timeActive ? 'true' : 'false' }}"
        aria-controls="timeSubmenu"
        class="{{ $timeActive ? 'active' : '' }}">
            <i class="bi bi-clock-history me-2"></i> Zeit Management
        </a>
        <div class="collapse submenu {{ $timeActive ? 'show' : '' }}" id="timeSubmenu">
            <a href="{{ route('admin.time.overview') }}" class="{{ request()->is('admin/time/overview') ? 'active' : '' }}">Wochenübersicht</a>
            <a href="{{ route('admin.time.logs') }}" class="{{ request()->is('admin/time/logs') ? 'active' : '' }}">Machine Zeiten</a>
            <a href="{{ route('admin.time.records') }}" class="{{ request()->is('admin/time/records*') ? 'active' : '' }}">Mann Zeiten</a>
            <a href="{{ route('admin.time.compare') }}" class="{{ request()->is('admin/time/compare') ? 'active' : '' }}">Zeit Vergleichen</a>
            <a href="{{ route('admin.time.change') }}" class="{{ request()->is('admin/time/change') ? 'active' : '' }}">Nachtragsanträge</a>
        </div>
    @endif

    {{-- Suppliers with submenu --}}
    @if(config('modules.suppliers'))
        @php
            $supplierActive = request()->is('admin/suppliers*');
        @endphp
        <a data-bs-toggle="collapse" href="#supplierSubmenu" role="button"
        aria-expanded="{{ $supplierActive ? 'true' : 'false' }}"
        aria-controls="supplierSubmenu"
        class="{{ $supplierActive ? 'active' : '' }}">
            <i class="bi bi-truck me-2"></i> Lieferant Management
        </a>
        <div class="collapse submenu {{ $supplierActive ? 'show' : '' }}" id="supplierSubmenu">
            <a href="{{ route('admin.suppliers.index') }}" class="{{ request()->is('admin/suppliers') ? 'active' : '' }}">Alle Lieferanten</a>
        </div>
    @endif

    {{-- Emails with submenu --}}
    @if(config('modules.emails'))
        @php
            $emailActive = request()->is('admin/emails*');
        @endphp
        <a data-bs-toggle="collapse" href="#emailSubmenu" role="button"
        aria-expanded="{{ $emailActive ? 'true' : 'false' }}"
        aria-controls="emailSubmenu"
        class="{{ $emailActive ? 'active' : '' }}">
            <i class="bi bi-envelope-open me-2"></i> Email Management
        </a>
        <div class="collapse submenu {{ $emailActive ? 'show' : '' }}" id="emailSubmenu">
            <a href="{{ route('admin.emails') }}" class="{{ request()->is('admin/emails') ? 'active' : '' }}">Inbox</a>
            <a href="{{ route('admin.emails.sent') }}" class="{{ request()->is('admin/emails/sent') ? 'active' : '' }}">Sent</a>
        </div>
    @endif

    {{-- Feedback-Details --}}
    @if(config('modules.feedback'))
        @php
            $feedbackActive = request()->is('admin/feedback*');
        @endphp
        <a data-bs-toggle="collapse" href="#feedbackSubmenu" role="button"
        aria-expanded="{{ $feedbackActive ? 'true' : 'false' }}"
        aria-controls="feedbackSubmenu"
        class="{{ $feedbackActive ? 'active' : '' }}">
            <i class="bi bi-chat-left-text me-2"></i> Feedback Management
        </a>
        <div class="collapse submenu {{ $feedbackActive ? 'show' : '' }}" id="feedbackSubmenu">
            <a href="{{ route('admin.feedback.index') }}" class="{{ request()->is('admin/feedback') ? 'active' : '' }}">Feedbacks</a>
        </div>
    @endif

    {{-- Tablar-übersicht --}}
    @if(config('modules.tablar'))
        @php
            $lagers = \App\Models\Lager::orderBy('name')->get();
            $lagerActive = request()->is('admin/lager*');
            $tablarActive = request()->is('admin/tablar*');
            $lagerMenuActive = $lagerActive || $tablarActive;

            $activeLagerId = request()->route('lager_id');
        @endphp
        <a data-bs-toggle="collapse" href="#lagerSubmenu" role="button"
        aria-expanded="{{ $lagerMenuActive ? 'true' : 'false' }}"
        aria-controls="lagerSubmenu"
        class="{{ $lagerMenuActive ? 'active' : '' }}">
            <i class="bi bi-boxes me-2"></i> Lager Management
        </a>
        <div class="collapse submenu {{ $lagerMenuActive ? 'show' : '' }}" id="lagerSubmenu">

            <a href="{{ route('admin.lager.index') }}"
                class="{{ request()->routeIs('admin.lager.index') ? 'active' : '' }}">
                Lager Übersicht
            </a>

            @foreach($lagers as $lager)
                @php
                    $isThisLagerActive = $activeLagerId == $lager->id;
                @endphp

                <a data-bs-toggle="collapse" href="#lagerSubmenu{{ $lager->id }}" role="button"
                aria-expanded="{{ $isThisLagerActive ? 'true' : 'false' }}"
                aria-controls="lagerSubmenu{{ $lager->id }}"
                class="submenu-parent {{ $isThisLagerActive ? 'active' : '' }}">
                    <i class="bi bi-archive me-1"></i> {{ $lager->name }}
                </a>

                <div class="collapse submenu-nested {{ $isThisLagerActive ? 'show' : '' }}"
                    id="lagerSubmenu{{ $lager->id }}">
                    <a href="{{ route('admin.tablar.overview', $lager->id) }}"
                    class="{{ request()->routeIs('admin.tablar.overview') && $isThisLagerActive ? 'active' : '' }}">
                        Übersicht
                    </a>
                    <a href="{{ route('admin.tablar.index', $lager->id) }}"
                    class="{{ request()->routeIs('admin.tablar.index') && $isThisLagerActive ? 'active' : '' }}">
                        Materialverwaltung
                    </a>
                </div>
            @endforeach

        </div>
    @endif

    {{-- Settings --}}
    @if(config('modules.settings'))
        @php
            $settingsActive = request()->is('admin/settings*');
        @endphp
        <a data-bs-toggle="collapse" href="#settingsSubmenu" role="button"
        aria-expanded="{{ $settingsActive ? 'true' : 'false' }}"
        aria-controls="settingsSubmenu"
        class="{{ $settingsActive ? 'active' : '' }}">
            <i class="bi bi-gear me-2"></i> Einstellungen
        </a>
        <div class="collapse submenu {{ $settingsActive ? 'show' : '' }}" id="settingsSubmenu">
            <a href="{{ route('admin.settings.machines') }}" class="{{ request()->is('admin/settings/machines*') ? 'active' : '' }}">Machinen</a>
            <a href="{{ route('admin.settings.machine-status') }}" class="{{ request()->is('admin/settings/machine-status*') ? 'active' : '' }}">Machine Status</a>
            <a href="{{ route('admin.settings.material-thresholds') }}" class="{{ request()->is('admin/settings/material-thresholds*') ? 'active' : '' }}">Material Schwellenwerte</a>
            <a href="{{ route('admin.settings.project-status') }}" class="{{ request()->is('admin/settings/project-status*') ? 'active' : '' }}">Projekt Status</a>
            <a href="{{ route('admin.settings.project-service') }}" class="{{ request()->is('admin/settings/project-service*') ? 'active' : '' }}">Projekt Leistung</a>
            <a href="{{ route('admin.settings.email_templates.index') }}" class="{{ request()->is('admin/settings/email_templates*') ? 'active' : '' }}">Email Template</a>
            <a href="#" class="">Logs</a>
        </div>
    @endif

    <hr class="text-secondary">
</div>
