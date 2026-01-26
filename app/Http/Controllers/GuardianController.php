<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    /**
     * Display a listing of guardians.
     */
    public function index(Request $request)
    {
        $query = Guardian::withCount('students');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $guardians = $query->orderBy('name')->paginate(20);
        
        return view('guardians.index', compact('guardians'));
    }

    /**
     * Show the form for creating a new guardian.
     */
    public function create()
    {
        return view('guardians.create');
    }

    /**
     * Store a newly created guardian.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);
        
        $guardian = Guardian::create($request->all());
        
        return redirect()->route('guardians.show', $guardian)
            ->with('success', 'Responsável cadastrado com sucesso!');
    }

    /**
     * Display the specified guardian.
     */
    public function show(Guardian $guardian)
    {
        $guardian->load('students');
        
        return view('guardians.show', compact('guardian'));
    }

    /**
     * Show the form for editing the specified guardian.
     */
    public function edit(Guardian $guardian)
    {
        return view('guardians.edit', compact('guardian'));
    }

    /**
     * Update the specified guardian.
     */
    public function update(Request $request, Guardian $guardian)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);
        
        $guardian->update($request->all());
        
        return redirect()->route('guardians.show', $guardian)
            ->with('success', 'Responsável atualizado com sucesso!');
    }

    /**
     * Remove the specified guardian.
     */
    public function destroy(Guardian $guardian)
    {
        if ($guardian->students()->count() > 0) {
            return back()->with('error', 'Não é possível excluir um responsável com alunos vinculados.');
        }
        
        $guardian->delete();
        
        return redirect()->route('guardians.index')
            ->with('success', 'Responsável excluído com sucesso!');
    }
}
