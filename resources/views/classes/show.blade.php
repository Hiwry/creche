@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('classes.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">{{ $class->name }}</h1>
        <span class="badge badge-{{ $class->status === 'active' ? 'success' : 'secondary' }}" style="margin-left: 10px;">
            {{ $class->status === 'active' ? 'Ativa' : 'Inativa' }}
        </span>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('classes.edit', $class) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>
</div>

<div class="grid grid-2">
    <!-- Class Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informações da Turma</h3>
        </div>
        
        <div style="display: grid; gap: 15px;">
            @if($class->teacher)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Professor:</span>
                <div style="font-weight: 500;">{{ $class->teacher->name }}</div>
            </div>
            @endif
            
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Horário:</span>
                <div style="font-weight: 500;">{{ $class->formatted_schedule }}</div>
            </div>
            
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Dias:</span>
                <div style="font-weight: 500;">{{ $class->formatted_days }}</div>
            </div>
            
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Alunos:</span>
                <div style="font-weight: 500;">
                    {{ $class->active_students_count }}
                    @if($class->capacity)
                    / {{ $class->capacity }}
                    @endif
                </div>
            </div>
            
            @if($class->monthly_fee)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Mensalidade:</span>
                <div style="font-size: 1.25rem; font-weight: 600; color: #10B981;">
                    R$ {{ number_format($class->monthly_fee, 2, ',', '.') }}
                </div>
            </div>
            @endif
            
            @if($class->description)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Descrição:</span>
                <div>{{ $class->description }}</div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Enrolled Students -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Alunos Matriculados</h3>
        </div>
        
        @if($class->activeEnrollments->count() > 0)
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach($class->activeEnrollments as $enrollment)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: #F8F9FC; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($enrollment->student->name) }}&background=7C3AED&color=fff" 
                         style="width: 35px; height: 35px; border-radius: 50%;">
                    <span>{{ $enrollment->student->name }}</span>
                </div>
                <form action="{{ route('classes.remove-student', [$class, $enrollment]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" 
                            onclick="return confirm('Remover este aluno da turma?')">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-state" style="padding: 30px;">
            <i class="fas fa-users"></i>
            <p>Nenhum aluno matriculado</p>
        </div>
        @endif
    </div>
</div>
@endsection
