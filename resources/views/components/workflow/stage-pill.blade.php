@props(['stage'])

<span class="badge rounded-pill workflow-stage-pill"
      style="background-color: {{ $stage->color }}; color: #fff;">
    @if($stage->icon)
        <i class="bi {{ $stage->icon }} me-1"></i>
    @endif
    {{ $stage->name }}
</span>
