<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" class="form-control" id="name" name="name"
        value="{{ old('name', $shelf->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="code" class="form-label">Code (optional)</label>
    <input type="text" class="form-control" id="code" name="code"
        value="{{ old('code', $shelf->code ?? '') }}">
</div>

<div class="mb-3">
    <label for="is_active" class="form-label">Aktiv</label>
    <select class="form-select" id="is_active" name="is_active" required>
        <option value="1" @selected(old('is_active', $shelf->is_active ?? true) == 1)>Ja</option>
        <option value="0" @selected(old('is_active', $shelf->is_active ?? true) == 0)>Nein</option>
    </select>
</div>