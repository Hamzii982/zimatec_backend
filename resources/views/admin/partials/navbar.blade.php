<nav class="navbar navbar-expand-lg navbar-light border-bottom shadow-sm px-3">
    <div class="container-fluid">

        {{-- 🍔 Hamburger (mobile only) --}}
        <button class="btn btn-light d-lg-none me-2" id="sidebarToggle" type="button">
            <i class="bi bi-list fs-5"></i>
        </button>
        {{-- Left Side --}}

        {{-- 🔍 Search Bar (desktop) --}}
        <form class="d-none d-lg-flex justify-content-center w-100 position-relative" role="search" method="GET" action="{{ route('admin.search') }}" id="desktopSearchForm">
            <div class="input-group" style="max-width: 600px; width: 100%;">
                <input type="search" name="keyword" class="form-control form-control-sm rounded-start-pill bg-light border-0 ps-3"
                    placeholder="Search..." aria-label="Search">
                <button class="btn btn-top-search rounded-end-pill px-3" type="submit" style="border: none;">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>

        {{-- 🔍 Mobile Search Toggle Button --}}
        <button class="btn btn-light d-lg-none ms-auto" id="mobileSearchToggle" type="button">
            <i class="bi bi-search fs-5"></i>
        </button>

        {{-- 🔍 Mobile Search Expandable Bar --}}
        <div class="mobile-search-overlay d-lg-none" id="mobileSearchOverlay">
            <form class="d-flex w-100" role="search" method="GET" action="{{ route('admin.search') }}">
                <button type="button" class="btn btn-light me-2 flex-shrink-0" id="mobileSearchClose">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <div class="input-group">
                    <input type="search" name="keyword" class="form-control form-control-sm bg-light border-0 ps-3"
                        placeholder="Search..." aria-label="Search" id="mobileSearchInput">
                    <button class="btn btn-top-search px-3" type="submit" style="border: none;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right side -->
        <div class="d-flex align-items-center ms-auto gap-2 gap-lg-3">

            <!-- 🔔 Notifications -->
            <div class="dropdown">
                <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    @php
                        $unreadCount = $notifications->where('is_read', false)->count();
                    @endphp
                    @if($unreadCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </button>
            
                <ul class="dropdown-menu dropdown-menu-end p-2 notification-dropdown"
                    style="width: 350px; max-height: 400px; overflow-y: auto;">

                    @forelse($notifications as $index => $note)
                        <li id="notification-{{ $note->id }}">
                            <div class="dropdown-item notification-item d-flex justify-content-between align-items-start {{ $note->is_read ? '' : 'fw-semibold' }}"
                                data-id="{{ $note->id }}"
                                data-url="{{ $note->url }}">

                                <div style="flex:1; cursor:pointer;"
                                    onclick="handleNotificationClick(this)"
                                    data-id="{{ $note->id }}"
                                    data-url="{{ $note->url }}">

                                    <div class="d-flex justify-content-between flex-wrap">
                                        <span class="badge bg-{{ $note->type == 'request' ? 'primary' : ($note->type == 'warning' ? 'warning' : 'secondary') }} text-dark mb-1">
                                            {{ ucfirst($note->type) }}
                                        </span>
                                        <small class="text-muted mb-1">{{ $note->created_at->diffForHumans() }}</small>
                                    </div>

                                    <div style="white-space:normal;">{{ $note->message }}</div>

                                    @if($note->user)
                                        <div class="text-small">{{ $note->user->name }}</div>
                                    @endif
                                </div>

                                <button class="btn btn-sm btn-link text-danger ms-2"
                                        onclick="deleteNotification(event, {{ $note->id }})"
                                        title="Löschen">🗑️</button>
                            </div>
                        </li>

                        @if($index < $notifications->count() - 1)
                            <hr class="dropdown-divider my-1" id="divider-{{ $note->id }}">
                        @endif
                    @empty
                        <li class="text-center text-muted">Keine Benachrichtigungen</li>
                    @endforelse
                </ul>
            </div>

            <!-- 🧑‍💼 User Menu -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle user-menu" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 me-2 text-primary"></i>
                    <span class="fw-semibold username-text d-none d-lg-inline">{{ Auth::user()->name ?? 'Admin' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                    <li><a class="dropdown-item" href="{{ route('admin.profile') }}">Profil verwalten</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">Abmelden</button>
                        </form>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // DELETE FUNCTION
    async function deleteNotification(event, id) {
        event.stopPropagation(); // 🔥 prevents triggering click navigation
    
        if (!confirm("Benachrichtigung wirklich löschen?")) return;
    
        try {
            const res = await fetch(`/admin/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
    
            if (!res.ok) throw new Error();
    
            // 🔥 Remove from DOM instantly
            document.getElementById(`notification-${id}`)?.remove();
            document.getElementById(`divider-${id}`)?.remove();
    
        } catch {
            alert("Fehler beim Löschen");
        }
    }
    
    // EXISTING CLICK HANDLER (if not already separated)
    function handleNotificationClick(el) {
        const url = el.getAttribute('data-url');
        if (url) window.location.href = url;
    }
</script>
