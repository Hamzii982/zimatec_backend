<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lager;
use Illuminate\Http\Request;
use App\Models\Shelf;

class ShelfController extends Controller
{
    public function index($lagerId)
    {
        // Fetch shelves for the given lager
        $shelves = Shelf::where('lager_id', $lagerId)->get();
        $lager = Lager::findOrFail($lagerId);
        return view('admin.shelf.index', compact('shelves', 'lagerId', 'lager'));
    }

    public function create($lagerId)
    {
        $lager = Lager::findOrFail($lagerId);
        return view('admin.shelf.create', compact('lagerId', 'lager'));
    }

    public function store(Request $request, $lagerId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Shelf::create([
            'lager_id' => $lagerId,
            'name' => $request->name,
            'is_active' => true,
        ]);

        return redirect()->route('admin.shelf.index', $lagerId)->with('success', 'Shelf created successfully.');
    }

    public function edit($lagerId, $id)
    {
        $shelf = Shelf::findOrFail($id);
        $lager = Lager::findOrFail($lagerId);
        return view('admin.shelf.edit', compact('shelf', 'lagerId', 'lager'));
    }

    public function update(Request $request, $lagerId, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $shelf = Shelf::findOrFail($id);
        $shelf->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.shelf.index', $lagerId)->with('success', 'Shelf updated successfully.');
    }

    public function destroy($lagerId, $id)
    {
        $shelf = Shelf::findOrFail($id);
        $shelf->delete();

        return redirect()->route('admin.shelf.index', $lagerId)->with('success', 'Shelf deleted successfully.');
    }
}
