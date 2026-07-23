@props(['percent' => 0, 'color' => '#0d6efd'])

@php
    $safe = max(0, min(100, (int) $percent));
@endphp

<div class="progress" style="height: 6px;" role="progressbar" aria-valuenow="{{ $safe }}" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar"
         style="width: {{ $safe }}%; background-color: {{ $color }}; transition: width .4s ease;"
         data-workflow-progress="{{ $safe }}"></div>
</div>
