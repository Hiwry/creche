<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request)
    {
        try {
            $query = ClassModel::with(['teacher', 'activeEnrollments']);
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $classes = $query->orderBy('name')->paginate(20);
            
            return view('classes.index', compact('classes'));
        } catch (\Throwable $e) {
            return response()->view('errors.500', ['exception' => $e], 500);
        }
    }

    /**
     * Show the form for creating a new class.
     */
    public function create()
    {
        try {
            $teachers = User::where('role', 'teacher')->orderBy('name')->get();
            
            return view('classes.create', compact('teachers'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao carregar formulário: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'teacher_id' => 'nullable|exists:users,id',
                'days_of_week' => 'required|array|min:1',
                'days_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'capacity' => 'nullable|integer|min:1',
            ]);
            
            $class = ClassModel::create([
                'name' => $request->name,
                'description' => $request->description,
                'teacher_id' => $request->teacher_id,
                'days_of_week' => $request->days_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'capacity' => $request->capacity,
                'status' => 'active',
            ]);
            
            return redirect()->route('classes.show', $class)
                ->with('success', 'Turma criada com sucesso!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao criar turma: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified class.
     */
    public function show(ClassModel $class)
    {
        try {
            $class->load([
                'teacher',
                'activeEnrollments.student',
                'attendanceLogs' => fn($q) => $q->latest('date')->limit(10),
            ]);
            
            return view('classes.show', compact('class'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao exibir turma: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(ClassModel $class)
    {
        try {
            $teachers = User::where('role', 'teacher')->orderBy('name')->get();
            
            return view('classes.edit', compact('class', 'teachers'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao carregar formulário: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, ClassModel $class)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'teacher_id' => 'nullable|exists:users,id',
                'days_of_week' => 'required|array|min:1',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'capacity' => 'nullable|integer|min:1',
                'status' => 'required|in:active,inactive',
            ]);
            
            $class->update([
                'name' => $request->name,
                'description' => $request->description,
                'teacher_id' => $request->teacher_id,
                'days_of_week' => $request->days_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'capacity' => $request->capacity,
                'status' => $request->status,
            ]);
            
            return redirect()->route('classes.show', $class)
                ->with('success', 'Turma atualizada com sucesso!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao atualizar turma: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified class.
     */
    public function destroy(ClassModel $class)
    {
        try {
            // Cancel all enrollments first
            $class->enrollments()->update(['status' => 'cancelled']);
            
            $class->update(['status' => 'inactive']);
            
            return redirect()->route('classes.index')
                ->with('success', 'Turma desativada com sucesso!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao desativar turma: ' . $e->getMessage());
        }
    }
    
    /**
     * Enroll a student in the class.
     */
    public function enrollStudent(Request $request, ClassModel $class)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id',
            ]);
            
            // Check if already enrolled
            $existing = Enrollment::where('student_id', $request->student_id)
                ->where('class_id', $class->id)
                ->where('status', 'active')
                ->first();
                
            if ($existing) {
                return back()->with('error', 'Aluno já está matriculado nesta turma!');
            }
            
            Enrollment::create([
                'student_id' => $request->student_id,
                'class_id' => $class->id,
                'status' => 'active',
                'start_date' => now(),
            ]);
            
            return back()->with('success', 'Aluno matriculado com sucesso!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao matricular aluno: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove a student from the class.
     */
    public function removeStudent(ClassModel $class, Enrollment $enrollment)
    {
        try {
            $enrollment->update([
                'status' => 'cancelled',
                'end_date' => now(),
            ]);
            
            return back()->with('success', 'Matrícula cancelada com sucesso!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao cancelar matrícula: ' . $e->getMessage());
        }
    }
}
