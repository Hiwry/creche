<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Setting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display today's attendance.
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $dayOfWeek = Carbon::parse($date)->translatedFormat('l');
        
        // Get active classes and filter enrollments if search is present
        $classes = ClassModel::active()
            ->with(['activeEnrollments' => function($q) use ($request) {
                $q->whereHas('student', function($sq) use ($request) {
                    if ($request->filled('search')) {
                        $sq->where('name', 'like', '%' . $request->search . '%');
                    }
                })->with('student');
            }])
            ->whereHas('activeEnrollments.student', function($q) use ($request) {
                if ($request->filled('search')) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                }
            })
            ->orderBy('start_time')
            ->get();
        
        // Get today's logs
        $logs = AttendanceLog::forDate($date)
            ->with(['student', 'classModel'])
            ->get()
            ->keyBy(function ($log) {
                return $log->student_id . '-' . $log->class_id;
            });
        
        return view('attendance.index', compact('classes', 'logs', 'date'));
    }
    
    /**
     * Check-in a student.
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
        ]);
        
        $class = ClassModel::findOrFail($request->class_id);
        $student = Student::findOrFail($request->student_id);
        [$expectedStart, $expectedEnd] = $this->resolveExpectedSchedule($student, $class);
        
        $log = AttendanceLog::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'date' => $request->date,
            ],
            [
                'check_in' => $request->input('check_in', Carbon::now()->format('H:i')),
                'expected_start' => $expectedStart,
                'expected_end' => $expectedEnd,
                'registered_by' => auth()->id(),
            ]
        );
        
        return back()->with('success', 'Entrada registrada!');
    }
    
    /**
     * Check-out a student.
     */
    public function checkOut(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
        ]);
        
        $log = AttendanceLog::where('student_id', $request->student_id)
            ->where('class_id', $request->class_id)
            ->where('date', $request->date)
            ->first();
            
        if (!$log) {
            return back()->with('error', 'Registro de entrada não encontrado!');
        }

        if (!$log->expected_start || !$log->expected_end) {
            $student = Student::find($log->student_id);
            $class = $log->class_id ? ClassModel::find($log->class_id) : null;
            if ($student) {
                [$expectedStart, $expectedEnd] = $this->resolveExpectedSchedule($student, $class);
                $log->expected_start = $expectedStart;
                $log->expected_end = $expectedEnd;
            }
        }
        
        $log->check_out = $request->input('check_out', Carbon::now()->format('H:i'));
        $log->picked_up_by = $request->picked_up_by;
        $log->notes = $request->notes;
        $log->save();
        
        // Calculate extra time
        $tolerance = Setting::getExtraHourTolerance();
        $hourlyRate = Setting::getExtraHourRate();
        $log->updateExtraCalculations($hourlyRate, $tolerance);
        
        return back()->with('success', 'Saída registrada!');
    }
    
    /**
     * Quick attendance registration.
     */
    public function quickRegister(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'type' => 'required|in:check_in,check_out',
        ]);
        
        $class = ClassModel::findOrFail($request->class_id);
        $student = Student::findOrFail($request->student_id);
        $now = Carbon::now()->format('H:i');
        
        $date = Carbon::parse($request->date)->format('Y-m-d');
        
        $log = AttendanceLog::where('student_id', $request->student_id)
            ->where('class_id', $request->class_id)
            ->whereDate('date', $date)
            ->firstOrNew([
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'date' => $date,
            ]);
        
        [$expectedStart, $expectedEnd] = $this->resolveExpectedSchedule($student, $class);
        $log->expected_start = $expectedStart;
        $log->expected_end = $expectedEnd;
        $log->registered_by = auth()->id();
        
        if ($request->type === 'check_in') {
            $log->check_in = $now;
        } else {
            $log->check_out = $now;
            
            // Calculate extra time
            $tolerance = Setting::getExtraHourTolerance();
            $hourlyRate = Setting::getExtraHourRate();
            $log->extra_minutes = $log->calculateExtraMinutes($tolerance);
            $log->extra_charge = $log->calculateExtraCharge($hourlyRate, $tolerance);
        }
        
        $log->save();
        
        $message = $request->type === 'check_in' ? 'Entrada' : 'Saída';
        return back()->with('success', "{$message} registrada às {$now}!");
    }
    
    /**
     * Monthly extra hours report.
     */
    public function extraHoursReport(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        
        $query = AttendanceLog::forMonth($year, $month)
            ->with(['student', 'classModel']);

        if (!$request->filled('student_id')) {
            $query->where('extra_minutes', '>', 0);
        }

        $logs = $query->when($request->filled('student_id'), function($q) use ($request) {
                $q->where('student_id', $request->student_id);
            })
            ->orderBy('date', 'desc')
            ->get();
        
        // Group by student
        $byStudent = $logs->groupBy('student_id')->map(function ($studentLogs) {
            return [
                'student' => $studentLogs->first()->student,
                'total_minutes' => $studentLogs->sum('extra_minutes'),
                'total_charge' => $studentLogs->sum('extra_charge'),
                'days' => $studentLogs->count(),
                'logs' => $studentLogs,
            ];
        })->sortByDesc('total_minutes');
        
        $summary = [
            'total_minutes' => $logs->sum('extra_minutes'),
            'total_charge' => $logs->sum('extra_charge'),
            'students_count' => $byStudent->count(),
        ];
        
        $selectedStudent = null;
        if ($request->filled('student_id')) {
            $selectedStudent = Student::with('activeEnrollments.classModel')->find($request->student_id);
        }

        $extraHourRate = Setting::getExtraHourRate();
        $extraHourTolerance = Setting::getExtraHourTolerance();

        return view('attendance.extra-hours', compact(
            'byStudent',
            'summary',
            'year',
            'month',
            'selectedStudent',
            'extraHourRate',
            'extraHourTolerance'
        ));
    }

    /**
     * Store extra hours log from report page.
     */
    public function storeExtraHours(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'required|date_format:H:i|after_or_equal:check_in',
            'class_id' => 'nullable|exists:classes,id',
            'year' => 'nullable|integer',
            'month' => 'nullable|integer',
        ]);

        $student = Student::with('activeEnrollments.classModel')->findOrFail($request->student_id);

        $classId = $request->filled('class_id') ? (int) $request->class_id : null;
        $classModel = null;
        if ($classId) {
            $classModel = ClassModel::find($classId);
        } elseif ($student->activeEnrollments->count() > 0) {
            $classModel = $student->activeEnrollments->first()->classModel;
            $classId = $classModel?->id;
        }

        [$expectedStart, $expectedEnd] = $this->resolveExpectedSchedule($student, $classModel);

        if (!$expectedStart || !$expectedEnd) {
            return back()->with('error', 'Horário previsto não encontrado. Defina o horário na turma ou no aluno.');
        }

        $log = AttendanceLog::updateOrCreate(
            [
                'student_id' => $student->id,
                'class_id' => $classId,
                'date' => Carbon::parse($request->date)->format('Y-m-d'),
            ],
            [
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'expected_start' => $expectedStart,
                'expected_end' => $expectedEnd,
                'registered_by' => auth()->id(),
            ]
        );

        $tolerance = Setting::getExtraHourTolerance();
        $hourlyRate = Setting::getExtraHourRate();
        $log->extra_minutes = $log->calculateExtraMinutes($tolerance);
        $log->extra_charge = $log->calculateExtraCharge($hourlyRate, $tolerance);
        $log->save();

        $message = $log->wasRecentlyCreated
            ? 'Registro criado e horas extras calculadas!'
            : 'Registro atualizado e horas extras recalculadas!';

        return back()->with('success', $message);
    }
    
    /**
     * Edit attendance log.
     */
    public function edit(AttendanceLog $log)
    {
        $hourlyRate = Setting::getExtraHourRate();
        $tolerance = Setting::getExtraHourTolerance();
        $autoExtraMinutes = $log->calculateExtraMinutes($tolerance);

        $isManualExtra = false;
        if ($log->extra_minutes !== null) {
            $storedMinutes = (int) ($log->extra_minutes ?? 0);
            $autoMinutes = (int) $autoExtraMinutes;
            $isManualExtra = ($storedMinutes !== $autoMinutes);
        }

        return view('attendance.edit', compact(
            'log',
            'hourlyRate',
            'tolerance',
            'autoExtraMinutes',
            'isManualExtra'
        ));
    }
    
    /**
     * Update attendance log.
     */
    public function update(Request $request, AttendanceLog $log)
    {
        $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'extra_minutes' => 'nullable|integer|min:0',
            'extra_manual' => 'nullable|boolean',
        ]);

        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = Carbon::createFromFormat('H:i', $request->check_in);
            $checkOut = Carbon::createFromFormat('H:i', $request->check_out);
            if ($checkOut->lt($checkIn)) {
                return back()
                    ->withInput()
                    ->withErrors(['check_out' => 'O horário de saída deve ser após a entrada.']);
            }
        }
        
        $log->check_in = $request->check_in;
        $log->check_out = $request->check_out;
        $log->picked_up_by = $request->picked_up_by;
        $log->notes = $request->notes;
        $log->save();
        
        $tolerance = Setting::getExtraHourTolerance();
        $hourlyRate = Setting::getExtraHourRate();

        $student = Student::find($log->student_id);
        $class = $log->class_id ? ClassModel::find($log->class_id) : null;
        if ($student) {
            [$expectedStart, $expectedEnd] = $this->resolveExpectedSchedule($student, $class);
            $log->expected_start = $expectedStart;
            $log->expected_end = $expectedEnd;
        }

        if ($request->boolean('extra_manual')) {
            $log->extra_minutes = (int) $request->input('extra_minutes', 0);
            $log->extra_charge = round(($log->extra_minutes / 60) * $hourlyRate, 2);
        } else {
            $log->extra_minutes = $log->calculateExtraMinutes($tolerance);
            $log->extra_charge = $log->calculateExtraCharge($hourlyRate, $tolerance);
        }
        
        $log->save();
        
        return redirect()->route('attendance.index', ['date' => $log->date->toDateString()])
            ->with('success', 'Registro atualizado!');
    }

    private function resolveExpectedSchedule(Student $student, ?ClassModel $classModel): array
    {
        $expectedStart = $student->start_time ?? $classModel?->start_time;
        $expectedEnd = $student->end_time ?? $classModel?->end_time;

        return [$expectedStart, $expectedEnd];
    }

    public function destroy(AttendanceLog $log)
    {
        $log->delete();
        return back()->with('success', 'Registro removido com sucesso!');
    }
}
