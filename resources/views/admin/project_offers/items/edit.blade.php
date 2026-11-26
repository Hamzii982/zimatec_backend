@extends('admin.layouts.index')

@section('content')

<div class="container mt-4">

    <div class="card shadow-sm">

        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                <i class="bi bi-pencil-square"></i>
                Kalkulations-Positionen bearbeiten
            </h5>
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.project_offers.items.update', [$offer->id, $calculation->id]) }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Name der Bezeichnung</label>
                        <input type="text" name="designation" class="form-control" required
                               value="{{ old('designation', $calculation->designation) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Anzahl</label>
                        <input type="number" name="t_pieces" value="{{ old('t_pieces', $calculation->pieces) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Zusätzliche Steuer (%)</label>
                        <input type="number" name="extra_tax" value="{{ old('extra_tax', $calculation->extra_tax ?? '') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Angebotsprozentsatz (%)</label>
                        <input type="number" name="final_offer" value="{{ old('final_offer', $calculation->final_offer ?? '') }}" class="form-control">
                    </div>
                </div>

                <div id="items-container">

                    @foreach($calculation->items as $i => $item)
                    <div class="calc-item card border rounded shadow-sm p-3 mb-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="m-0 fw-bold text-primary">
                                Position <span class="pos-number">{{ $i + 1 }}</span>
                            </h6>

                            <button type="button" class="btn btn-outline-danger btn-sm remove-item" style="display:{{ $i > 0 ? 'block' : 'none' }};">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        {{-- Service Selection --}}
                        <label class="form-label fw-semibold">Auftragsleistung wählen</label>
                        <div class="service-select-container">

                            @foreach($item->service->getCascadingSelectOptions() as $level => $options)
                        
                                <select class="form-select service-select mt-2" data-level="{{ $level }}">
                                    <option value="">-- auswählen --</option>
                        
                                    @foreach($options as $opt)
                                        <option value="{{ $opt['id'] }}" {{ $opt['selected'] ? 'selected' : '' }}>
                                            {{ $opt['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                        
                            @endforeach
                        
                        </div>

                        <input type="hidden" name="service_id[]" class="final_service_id" value="{{ $item->project_service_id }}">

                        <hr>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Stunden</label>
                                <input type="number" step="0.01" name="hours[]" class="form-control" value="{{ $item->hours }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">€/Std.</label>
                                <input type="number" step="0.01" name="price_per_hour[]" class="form-control calc-field" value="{{ $item->price_per_hour }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Stück</label>
                                <input type="number" name="pieces[]" class="form-control calc-field" value="{{ $item->pieces }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Einzelpreis (€)</label>
                                <input type="number" step="0.01" name="price_per_unit[]" class="form-control calc-field" value="{{ $item->price_per_unit }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold d-block">Kostenart</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input type="radio" id="cost_{{ $i }}" name="cost_type[{{ $i }}]" value="cost" class="form-check-input" @if($item->cost_type == 'cost') checked @endif>
                                        <label for="cost_{{ $i }}" class="form-check-label">Betrag</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" id="material_{{ $i }}" name="cost_type[{{ $i }}]" value="material" class="form-check-input" @if($item->cost_type == 'material') checked @endif>
                                        <label for="material_{{ $i }}" class="form-check-label">Material</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" id="external_{{ $i }}" name="cost_type[{{ $i }}]" value="fremd_leistung" class="form-check-input" @if($item->cost_type == 'fremd_leistung') checked @endif>
                                        <label for="external_{{ $i }}" class="form-check-label">Fremd-leistung</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Notiz</label>
                                <input type="text" name="comment[]" class="form-control" value="{{ $item->comment }}">
                            </div>
                        </div>

                    </div>
                    @endforeach

                </div>

                <div class="text-center mb-4">
                    <button type="button" id="add-item" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Neue Position hinzufügen
                    </button>
                </div>

                <hr>

                <button class="btn btn-wechsel">
                    <i class="bi bi-check2-circle"></i> Änderungen speichern
                </button>

                <a href="{{ route('admin.project_offers.calculations', $offer->id) }}" class="btn btn-secondary">Abbrechen</a>

            </form>
        </div>
    </div>
</div>

{{-- JS --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    let itemCounter = {{ $calculation->items->count() }};

    // Add new item
    document.getElementById('add-item').addEventListener('click', () => {

        itemCounter++;

        let template = document.querySelector('.calc-item').cloneNode(true);

        // Reset fields
        template.querySelectorAll('input').forEach(i => {
            if (i.type === 'radio') return; 
            i.value = '';
        });

        template.querySelector('.pos-number').innerText = itemCounter;
        template.querySelector('.remove-item').style.display = 'block';
        template.querySelector('.final_service_id').value = '';

        // Reset radios
        template.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.checked = (radio.value === 'cost'); 
        });

        // Remove old selects
        template.querySelectorAll('.service-select').forEach(s => s.remove());

        // Update radio IDs
        template.querySelectorAll('.form-check-input').forEach((radio, index) => {
            let newName = `cost_type[${itemCounter-1}]`;
            radio.name = newName;
            let newId = radio.value + '_' + itemCounter + '_' + index;
            radio.id = newId;
            radio.parentNode.querySelector('label').setAttribute('for', newId);
        });

        // Add first select again
        template.querySelector('.service-select-container').innerHTML =
        `
            <select class="form-select service-select" data-level="0">
                <option value="">-- auswählen --</option>
                @foreach($rootServices as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        `;

        document.getElementById('items-container').appendChild(template);
    });

    // Remove item
    document.addEventListener('click', e => {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.calc-item').remove();
        }
    });

    // Multi-level select
    document.addEventListener('change', async e => {
        if (e.target.classList.contains('service-select')) {
            const row = e.target.closest('.calc-item');
            const container = row.querySelector('.service-select-container');
            const level = parseInt(e.target.dataset.level);
            const selectedId = e.target.value;

            container.querySelectorAll('.service-select').forEach(select => {
                if (parseInt(select.dataset.level) > level) select.remove();
            });

            if (selectedId) {
                const res = await fetch(`/admin/project_offers/children/${selectedId}`);
                const children = await res.json();

                if (children.length > 0) {
                    const newSelect = document.createElement('select');
                    newSelect.className = "form-select service-select mt-2";
                    newSelect.dataset.level = level + 1;

                    newSelect.innerHTML = `<option value="">-- auswählen --</option>`;
                    children.forEach(child => {
                        newSelect.innerHTML += `<option value="${child.id}">${child.name}</option>`;
                    });

                    container.appendChild(newSelect);
                }

                row.querySelector('.final_service_id').value = selectedId;
            } else {
                row.querySelector('.final_service_id').value = '';
            }
        }
    });

});
</script>

@endsection
