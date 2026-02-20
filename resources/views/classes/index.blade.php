@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Turmas</h1>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('classes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Turma
        </a>
    </div>
</div>

<!-- Classes Grid -->
<div class="grid grid-3" style="gap: 20px;">
    @forelse($classes as $class)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $class->name }}</h3>
            <span class="badge badge-{{ $class->status === 'active' ? 'success' : 'secondary' }}">
                {{ $class->status === 'active' ? 'Ativa' : 'Inativa' }}
            </span>
        </div>
        
        <div style="margin-bottom: 15px;">
            @if($class->teacher)
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                <i class="fas fa-user" style="color: #7C3AED;"></i>
                <span>{{ $class->teacher->name }}</span>
            </div>
            @endif
            
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                <i class="fas fa-calendar" style="color: #7C3AED;"></i>
                <span>{{ $class->formatted_days }}</span>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                <i class="fas fa-clock" style="color: #7C3AED;"></i>
                <span>{{ $class->formatted_schedule }}</span>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-users" style="color: #7C3AED;"></i>
                <span>{{ $class->active_students_count }} alunos</span>
                @if($class->capacity)
                <span style="color: #9CA3AF;">/ {{ $class->capacity }}</span>
                @endif
            </div>
        </div>
        
        @if($class->monthly_fee)
        <div style="font-size: 1.25rem; font-weight: 600; color: #10B981; margin-bottom: 15px;">
            R$ {{ number_format($class->monthly_fee, 2, ',', '.') }}/mês
        </div>
        @endif
        
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('classes.show', $class) }}" class="btn btn-secondary btn-sm" style="flex: 1;">
                <i class="fas fa-eye"></i> Ver
            </a>
            <a href="{{ route('classes.edit', $class) }}" class="btn btn-secondary btn-sm" style="flex: 1;">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>
    @empty
    <div class="card" style="grid-column: span 3;">
        <div class="empty-state">
            <i class="fas fa-chalkboard-teacher"></i>
            <h3>Nenhuma turma cadastrada</h3>
            <p>Clique em "Nova Turma" para criar</p>
        </div>
    </div>
    @endforelse
</div>

<div class="pagination">
    {{ $classes->links() }}
</div>
@endsection
