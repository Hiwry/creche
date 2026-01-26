<?php

namespace App\Http\Controllers;

use App\Models\SchoolMaterial;
use App\Models\Student;
use App\Models\StudentMaterial;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class SchoolMaterialController extends Controller
{
    public function index()
    {
        $materials = SchoolMaterial::all();
        return view('school-materials.index', compact('materials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
        ]);

        SchoolMaterial::create($validated);

        return redirect()->route('school-materials.index')->with('success', 'Material adicionado com sucesso!');
    }

    public function destroy(SchoolMaterial $schoolMaterial)
    {
        $schoolMaterial->delete();
        return redirect()->route('school-materials.index')->with('success', 'Material removido com sucesso!');
    }

    public function bulkCheck(Request $request)
    {
        $classId = $request->get('class_id');
        $classes = ClassModel::all();
        
        $query = Student::with(['studentMaterials.material' => function($q) {
            $q->where('is_active', true);
        }]);

        if ($classId) {
            $query->whereHas('activeEnrollments', function($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        $students = $query->where('status', 'active')->get();
        $materials = SchoolMaterial::where('is_active', true)->get();

        return view('school-materials.bulk-check', compact('students', 'materials', 'classes', 'classId'));
    }

    public function updateBulkCheck(Request $request)
    {
        $data = $request->input('materials', []);
        
        foreach ($data as $studentId => $materialData) {
            foreach ($materialData as $materialId => $received) {
                StudentMaterial::updateOrCreate(
                    ['student_id' => $studentId, 'material_id' => $materialId],
                    [
                        'received' => (bool)$received,
                        'received_at' => $received ? now() : null
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Checklist atualizado com sucesso!');
    }

    public function updateStudentChecklist(Request $request, Student $student)
    {
        $data = $request->input('materials', []);
        
        foreach ($data as $materialId => $received) {
            StudentMaterial::updateOrCreate(
                ['student_id' => $student->id, 'material_id' => $materialId],
                [
                    'received' => (bool)$received,
                    'received_at' => $received ? now() : null
                ]
            );
        }

        return redirect()->back()->with('success', 'Checklist do aluno atualizado com sucesso!');
    }
}
