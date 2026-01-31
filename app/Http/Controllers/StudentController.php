<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Guardian;
use App\Models\StudentHealth;
use App\Models\StudentDocument;
use App\Models\Enrollment;
use App\Models\ClassModel;
use App\Models\MonthlyFee;
use App\Models\MaterialFee;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request)
    {
        $query = Student::with(['guardian', 'activeEnrollments.classModel']);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('guardian', function ($gq) use ($search) {
                      $gq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('whatsapp', 'like', "%{$search}%");
                  });
            });
        }
        
        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Class filter
        if ($request->filled('class_id')) {
            $query->whereHas('activeEnrollments', function ($eq) use ($request) {
                $eq->where('class_id', $request->class_id);
            });
        }
        
        // Payment status filter
        if ($request->payment_status === 'paid') {
            $query->whereDoesntHave('monthlyFees', function ($fq) {
                $fq->whereIn('status', ['pending', 'partial', 'overdue']);
            });
        } elseif ($request->payment_status === 'pending') {
            $query->withPendingFees();
        }
        
        $students = $query->orderBy('name')->paginate(20);
        $classes = ClassModel::active()->orderBy('name')->get();
        
        return view('students.index', compact('students', 'classes'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create()
    {
        $guardians = Guardian::orderBy('name')->get();
        $classes = ClassModel::active()->orderBy('name')->get();
        
        return view('students.create', compact('guardians', 'classes'));
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:M,F,O',
            'guardian_id' => 'nullable|exists:guardians,id',
            // New guardian fields
            'guardian_name' => 'required_without:guardian_id|string|max:255',
            'guardian_cpf' => 'nullable|string|max:14',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_whatsapp' => 'nullable|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            // Class enrollment
            'class_id' => 'nullable|exists:classes,id',
            // Photo
            'photo' => 'nullable|image|max:2048',
            // Individual fields
            'monthly_fee' => 'required|numeric|min:0',
            'due_day' => 'required|integer|min:1|max:31',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Create or get guardian
            if ($request->filled('guardian_id')) {
                $guardianId = $request->guardian_id;
            } else {
                $guardian = Guardian::create([
                    'name' => $request->guardian_name,
                    'cpf' => $request->guardian_cpf,
                    'phone' => $request->guardian_phone,
                    'whatsapp' => $request->guardian_whatsapp,
                    'email' => $request->guardian_email,
                    'address' => $request->guardian_address,
                    'city' => $request->guardian_city,
                    'state' => $request->guardian_state,
                    'cep' => $request->guardian_cep,
                ]);
                $guardianId = $guardian->id;
            }
            
            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students/photos', 'public');
            }
            
            // Create student
            $student = Student::create([
                'guardian_id' => $guardianId,
                'name' => $request->name,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'photo' => $photoPath,
                'observations' => $request->observations,
                'status' => 'active',
                'authorized_pickups' => $request->authorized_pickups ? json_decode($request->authorized_pickups, true) : null,
                'monthly_fee' => $request->monthly_fee,
                'due_day' => $request->due_day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);
            
            // Create health record
            StudentHealth::create([
                'student_id' => $student->id,
                'health_plan_name' => $request->health_plan_name,
                'health_plan_number' => $request->health_plan_number,
                'health_plan_validity' => $request->health_plan_validity,
                'medications' => $request->medications,
                'allergies' => $request->allergies,
                'dietary_restrictions' => $request->dietary_restrictions,
                'medical_conditions' => $request->medical_conditions,
                'blood_type' => $request->blood_type,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
            ]);
            
            // Create enrollment if class selected
            if ($request->filled('class_id')) {
                Enrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $request->class_id,
                    'status' => 'active',
                    'start_date' => Carbon::today(),
                ]);
                
                // Create monthly fee for current month
                $monthlyFeeAmount = $request->monthly_fee;
                
                MonthlyFee::create([
                    'student_id' => $student->id,
                    'class_id' => $request->class_id,
                    'year' => Carbon::now()->year,
                    'month' => Carbon::now()->month,
                    'amount' => $monthlyFeeAmount,
                    'status' => 'pending',
                    'due_date' => $this->makeDueDate(Carbon::now()->year, Carbon::now()->month, (int) $request->due_day),
                ]);
            }
            
            // Create material fee for current year if selected
            if ($request->boolean('create_material_fee')) {
                MaterialFee::create([
                    'student_id' => $student->id,
                    'year' => Carbon::now()->year,
                    'amount' => Setting::getDefaultMaterialFee(),
                    'status' => 'pending',
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('students.show', $student)
                ->with('success', 'Aluno cadastrado com sucesso!');
                
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Erro ao cadastrar aluno: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student)
    {
        $student->load([
            'guardian',
            'health',
            'documents',
            'studentMaterials.material',
            'activeEnrollments.classModel',
            'invoices' => fn($q) => $q->orderBy('year', 'desc')->orderBy('month', 'desc'),
            'materialFees' => fn($q) => $q->orderBy('year', 'desc'),
            'attendanceLogs' => fn($q) => $q->withTrashed()->latest('date')->limit(30),
        ]);
        
        $allMaterials = \App\Models\SchoolMaterial::where('is_active', true)->get();
        $extraHoursSummary = $student->attendanceLogs()->sum('extra_charge');
        
        return view('students.show', compact('student', 'allMaterials', 'extraHoursSummary'));
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit(Student $student)
    {
        $student->load(['guardian', 'health']);
        $guardians = Guardian::orderBy('name')->get();
        $classes = ClassModel::active()->orderBy('name')->get();
        
        return view('students.edit', compact('student', 'guardians', 'classes'));
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:M,F,O',
            'status' => 'required|in:active,inactive,suspended',
            'photo' => 'nullable|image|max:2048',
            'monthly_fee' => 'required|numeric|min:0',
            'due_day' => 'required|integer|min:1|max:31',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);
        
        DB::beginTransaction();
        
        try {
            $data = $request->except(['photo', 'authorized_pickups']);
            $data['authorized_pickups'] = $request->authorized_pickups ? json_decode($request->authorized_pickups, true) : null;

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo
                if ($student->photo) {
                    Storage::disk('public')->delete($student->photo);
                }
                $data['photo'] = $request->file('photo')->store('students/photos', 'public');
            }
            
            $student->update($data);
            
            // Update pending monthly fees if fee or due day changed
            if ($request->filled('monthly_fee') || $request->filled('due_day')) {
                $pendingFees = MonthlyFee::where('student_id', $student->id)
                    ->pending()
                    ->get();
                    
                foreach ($pendingFees as $fee) {
                    $updateData = [];
                    
                    if ($request->filled('monthly_fee')) {
                        // Only update amount if it was using the old default/class fee
                        // Or simplifying: Just update to the new student fee as that's the intention
                        $updateData['amount'] = $request->monthly_fee;
                    }
                    
                    if ($request->filled('due_day')) {
                        // Keep month and year, update day
                        // Be careful with months that don't have day 30/31
                        try {
                            $newDate = Carbon::create($fee->year, $fee->month, (int)$request->due_day);
                            $updateData['due_date'] = $newDate;
                        } catch (\Exception $e) {
                            // Fallback to last day of month if invalid date (e.g. Feb 30)
                            $newDate = Carbon::create($fee->year, $fee->month, 1)->endOfMonth();
                            $updateData['due_date'] = $newDate;
                        }
                    }
                    
                    if (!empty($updateData)) {
                        $fee->update($updateData);
                    }
                }
            }
            
            // Update health record
            $student->health()->updateOrCreate(
                ['student_id' => $student->id],
                [
                    'health_plan_name' => $request->health_plan_name,
                    'health_plan_number' => $request->health_plan_number,
                    'health_plan_validity' => $request->health_plan_validity,
                    'medications' => $request->medications,
                'allergies' => $request->allergies,
                'dietary_restrictions' => $request->dietary_restrictions,
                'medical_conditions' => $request->medical_conditions,
                'blood_type' => $request->blood_type,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
            ]
        );
            
            DB::commit();
            
            return redirect()->route('students.show', $student)
                ->with('success', 'Aluno atualizado com sucesso!');
                
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Erro ao atualizar aluno: ' . $e->getMessage());
        }
    }

    private function makeDueDate(int $year, int $month, int $day): Carbon
    {
        $base = Carbon::create($year, $month, 1);
        $day = max(1, min($day, $base->daysInMonth));

        return Carbon::create($year, $month, $day);
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student)
    {
        $student->delete(); // Soft delete
        
        return redirect()->route('students.index')
            ->with('success', 'Aluno removido com sucesso!');
    }
    
    /**
     * Upload document for student.
     */
    public function uploadDocument(Request $request, Student $student)
    {
        $request->validate([
            'document' => 'required|file|max:10240', // 10MB
            'type' => 'required|string',
            'name' => 'required|string|max:255',
        ]);
        
        $file = $request->file('document');
        $path = $file->store("students/{$student->id}/documents", 'public');
        
        $document = StudentDocument::create([
            'student_id' => $student->id,
            'type' => $request->type,
            'name' => $request->name,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'notes' => $request->notes,
        ]);
        
        return back()->with('success', 'Documento enviado com sucesso!');
    }
    
    /**
     * Delete document.
     */
    public function deleteDocument(Student $student, StudentDocument $document)
    {
        if ($document->student_id !== $student->id) {
            abort(403);
        }
        
        Storage::disk('public')->delete($document->path);
        $document->delete();
        
        return back()->with('success', 'Documento excluído com sucesso!');
    }
}
