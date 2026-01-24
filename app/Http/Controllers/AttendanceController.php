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
        
        $log = AttendanceLog::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'date' => $request->date,
            ],
            [
                'check_in' => $request->input('check_in', Carbon::now()->format('H:i')),
                'expected_start' => $class->start_time,
                'expected_end' => $class->end_time,
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
        
        $log->expected_start = $class->start_time;
        $log->expected_end = $class->end_time;
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
             $selectedStudent = Student::find($request->student_id);
        }

        return view('attendance.extra-hours', compact('byStudent', 'summary', 'year', 'month', 'selectedStudent'));
    }
    
    /**
     * Edit attendance log.
     */
    public function edit(AttendanceLog $log)
    {
        return view('attendance.edit', compact('log'));
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
            'extra_charge' => 'nullable|numeric|min:0',
        ]);
        
        $log->check_in = $request->check_in;
        $log->check_out = $request->check_out;
        $log->picked_up_by = $request->picked_up_by;
        $log->notes = $request->notes;
        $log->save();
        
        // Check if manual overrides are present
        if ($request->filled('extra_minutes') || $request->filled('extra_charge')) {
            $log->extra_minutes = $request->input('extra_minutes', 0);
            $log->extra_charge = $request->input('extra_charge', 0);
        } else {
            // Recalculate based on times if not manually overriden
            $tolerance = Setting::getExtraHourTolerance();
            $hourlyRate = Setting::getExtraHourRate();
            $log->updateExtraCalculations($hourlyRate, $tolerance);
        }
        
        $log->save();
        
        return redirect()->route('attendance.index', ['date' => $log->date->toDateString()])
            ->with('success', 'Registro atualizado!');
    }

    public function destroy(AttendanceLog $log)
    {
        $log->delete();
        return back()->with('success', 'Registro removido com sucesso!');
    }
}
