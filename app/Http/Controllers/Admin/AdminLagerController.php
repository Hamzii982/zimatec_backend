<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lager;

class AdminLagerController extends Controller
{
    public function lager()
    {
        $lagers = Lager::with('activeMaterials')->get();
        return view('admin.lager.index', compact('lagers'));
    }

    public function createLager()
    {
        return view('admin.lager.create');
    }
    
    public function storeLager(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:material,werkzeug,holz',
            'is_active' => 'required|boolean',
            'status' => 'nullable|string|max:255',
        ]);

        Lager::create($data);

        return redirect()->route('admin.lager.index')->with('success', 'Lager erfolgreich erstellt.');
    }

    public function editLager($id)
    {
        $lager = Lager::findOrFail($id);
        return view('admin.lager.edit', compact('lager'));
    }

    public function updateLager(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:material,werkzeug,holz',
            'is_active' => 'required|boolean',
            'status' => 'nullable|string|max:255',
        ]);

        $lager = Lager::findOrFail($id);
        $lager->update($data);

        return redirect()->route('admin.lager.index')->with('success', 'Lager erfolgreich aktualisiert.');
    }

    public function destroyLager($id)
    {
        $lager = Lager::findOrFail($id);
        $lager->delete();

        return redirect()->route('admin.lager.index')->with('success', 'Lager erfolgreich gelöscht.');
    }

    public function showLager($id)
    {
        $lager = Lager::with('activeMaterials')->findOrFail($id);
        return view('admin.lager.show', compact('lager'));
    }
}
