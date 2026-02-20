<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use App\Models\SportSchedule;
use App\Models\SportEnrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SportController extends Controller
{
    /**
     * Display a listing of sports.
     */
    public function index()
    {
        $sports = Sport::withCount(['students'])
            ->with(['schedules'])
            ->orderBy('name')
            ->get();
            
        return view('sports.index', compact('sports'));
    }

    /**
     * Show the form for creating a new sport.
     */
    public function create()
    {
        return view('sports.create');
    }

    /**
     * Store a newly created sport.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'monthly_fee' => 'required|numeric|min:0',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required|string',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
        ]);

        DB::beginTransaction();
        try {
            $sport = Sport::create([
                'name' => $request->name,
                'monthly_fee' => $request->monthly_fee,
                'is_active' => true,
            ]);

            if ($request->filled('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $sport->schedules()->create($schedule);
                }
            }

            DB::commit();
            return redirect()->route('sports.index')->with('success', 'Esporte criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao criar esporte: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified sport.
     */
    public function show(Sport $sport)
    {
        $sport->load(['schedules', 'students' => function($q) {
            $q->wherePivot('status', 'active');
        }]);
        
        $availableStudents = Student::active()
            ->whereDoesntHave('sports', function($q) use ($sport) {
                $q->where('sports.id', $sport->id);
            })
            ->orderBy('name')
            ->get();

        return view('sports.show', compact('sport', 'availableStudents'));
    }

    /**
     * Show the form for editing the specified sport.
     */
    public function edit(Sport $sport)
    {
        $sport->load('schedules');
        return view('sports.edit', compact('sport'));
    }

    /**
     * Update the specified sport.
     */
    public function update(Request $request, Sport $sport)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'monthly_fee' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required|string',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
        ]);

        DB::beginTransaction();
        try {
            $sport->update([
                'name' => $request->name,
                'monthly_fee' => $request->monthly_fee,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Sync schedules: easiest is delete and recreate if complex, but lets try to be clean
            $sport->schedules()->delete();
            if ($request->filled('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $sport->schedules()->create($schedule);
                }
            }

            DB::commit();
            return redirect()->route('sports.index')->with('success', 'Esporte atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao atualizar esporte: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Enroll a student in the sport.
     */
    public function enroll(Request $request, Sport $sport)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'monthly_fee' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
        ]);

        $exists = SportEnrollment::where('sport_id', $sport->id)
            ->where('student_id', $request->student_id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return back()->with('error', 'Este aluno já está matriculado neste esporte.');
        }

        SportEnrollment::create([
            'sport_id' => $sport->id,
            'student_id' => $request->student_id,
            'monthly_fee' => $request->monthly_fee ?? $sport->monthly_fee,
            'start_date' => $request->start_date,
            'status' => 'active',
        ]);

        return back()->with('success', 'Aluno matriculado com sucesso!');
    }

    /**
     * Unenroll a student.
     */
    public function unenroll(SportEnrollment $enrollment)
    {
        $enrollment->update([
            'status' => 'inactive',
            'end_date' => Carbon::today(),
        ]);

        return back()->with('success', 'Matrícula encerrada com sucesso!');
    }

    /**
     * Remove the specified sport.
     */
    public function destroy(Sport $sport)
    {
        $sport->delete();
        return redirect()->route('sports.index')->with('success', 'Esporte removido com sucesso!');
    }
}
