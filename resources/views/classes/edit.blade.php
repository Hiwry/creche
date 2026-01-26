@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('classes.show', $class) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">Editar Turma</h1>
    </div>
</div>

<div class="grid grid-2">
    <!-- Edit Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Dados da Turma</h3>
        </div>
        
        <form action="{{ route('classes.update', $class) }}" method="POST">
            @csrf
            @method('PUT')
            
            @if($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            <div class="form-group">
                <label class="form-label">Nome da Turma *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $class->name) }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Professor</label>
                <select name="teacher_id" class="form-control">
                    <option value="">Selecione um professor</option>
                    @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ old('teacher_id', $class->teacher_id) == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $class->description) }}</textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dias da Semana *</label>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    @foreach(\App\Models\ClassModel::DAYS_OF_WEEK as $key => $label)
                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                        <input type="checkbox" name="days_of_week[]" value="{{ $key }}"
                               {{ in_array($key, old('days_of_week', $class->days_of_week ?? [])) ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>
            

            
            <div class="form-group">
                <label class="form-label">Capacidade</label>
                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $class->capacity) }}" min="1">
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ old('status', $class->status) === 'active' ? 'selected' : '' }}>Ativa</option>
                    <option value="inactive" {{ old('status', $class->status) === 'inactive' ? 'selected' : '' }}>Inativa</option>
                </select>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
    
    <!-- Students Management -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-users" style="color: #7C3AED; margin-right: 10px;"></i>
                Alunos Matriculados ({{ $class->activeEnrollments->count() }})
            </h3>
        </div>
        
        <!-- Add Student Form -->
        <form action="{{ route('classes.enroll', $class) }}" method="POST" style="margin-bottom: 20px;">
            @csrf
            <div style="display: flex; gap: 10px;">
                <select name="student_id" class="form-control" style="flex: 1;" required>
                    <option value="">Selecione um aluno para matricular...</option>
                    @php
                        $enrolledIds = $class->activeEnrollments->pluck('student_id')->toArray();
                        $availableStudents = \App\Models\Student::active()
                            ->whereNotIn('id', $enrolledIds)
                            ->orderBy('name')
                            ->get();
                    @endphp
                    @foreach($availableStudents as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
        </form>
        
        @if($availableStudents->count() === 0 && $class->activeEnrollments->count() === 0)
        <div class="empty-state" style="padding: 20px;">
            <i class="fas fa-user-graduate"></i>
            <p>Nenhum aluno disponível. <a href="{{ route('students.create') }}">Cadastre um aluno primeiro.</a></p>
        </div>
        @endif
        
        <!-- Enrolled Students List -->
        @if($class->activeEnrollments->count() > 0)
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach($class->activeEnrollments as $enrollment)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F8F9FC; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="{{ $enrollment->student->photo ? asset('storage/' . $enrollment->student->photo) : 'https://ui-avatars.com/api/?name=' . urlencode($enrollment->student->name) . '&background=7C3AED&color=fff' }}" 
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <div>
                        <div style="font-weight: 500;">{{ $enrollment->student->name }}</div>
                        <div style="font-size: 0.8rem; color: #6B7280;">
                            Matrícula: {{ $enrollment->start_date->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 5px;">
                    <a href="{{ route('students.show', $enrollment->student) }}" class="btn btn-secondary btn-sm" title="Ver Aluno">
                        <i class="fas fa-eye"></i>
                    </a>
                    <form action="{{ route('classes.remove-student', [$class, $enrollment]) }}" method="POST" 
                          onsubmit="return confirm('Remover {{ $enrollment->student->name }} desta turma?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" title="Remover da Turma">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
