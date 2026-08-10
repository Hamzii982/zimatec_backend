@props(['percent' => 0, 'color' => '#002752'])

@php
    $safe = max(0, min(100, (int) $percent));
@endphp

<div class="workflow-progress" role="progressbar" aria-valuenow="{{ $safe }}" aria-valuemin="0" aria-valuemax="100">
    <div class="workflow-progress__bar"
         style="width: {{ $safe }}%; background-color: {{ $color }};"
         data-workflow-progress="{{ $safe }}"></div>
</div>
