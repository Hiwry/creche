@extends('layouts.app')

@section('content')
@php
    $selectedDate = \Carbon\Carbon::parse($date);
    $today = \Carbon\Carbon::today();
@endphp
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Entrada & Saída</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ $selectedDate->format('d/m/Y') }} ({{ ucfirst($selectedDate->locale('pt_BR')->translatedFormat('l')) }})
        </span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('attendance.index') }}" method="GET" class="filter-form" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" class="form-control" placeholder="Buscar aluno..." value="{{ request('search') }}">
            <input type="date" name="date" class="form-control" value="{{ $date }}">
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <a href="{{ route('attendance.report') }}" class="btn btn-secondary">
            <i class="fas fa-list-check"></i> Lista de Presença
        </a>
        <a href="{{ route('attendance.extra-hours') }}" class="btn btn-warning">
            <i class="fas fa-clock"></i> Horas Extras
        </a>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
    <div class="stat-card yellow">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['expected'] ?? 0 }}</div>
            <div class="stat-label">Alunos Esperados</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['present'] ?? 0 }}</div>
            <div class="stat-label">Presentes</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon">
            <i class="fas fa-user-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['pending'] ?? 0 }}</div>
            <div class="stat-label">Sem Registro</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon">
            <i class="fas fa-user-xmark"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['absences'] ?? 0 }}</div>
            <div class="stat-label">Faltas do Dia</div>
        </div>
    </div>
</div>

@forelse($classes as $class)
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chalkboard-teacher" style="color: #7C3AED; margin-right: 10px;"></i>
            {{ $class->name }}
        </h3>
        <span style="color: #6B7280;">
            {{ \Carbon\Carbon::parse($class->start_time)->format('H:i') }} - 
            {{ \Carbon\Carbon::parse($class->end_time)->format('H:i') }}
        </span>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Status</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                    <th>Horas Extras</th>
                    <th>Buscou</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($class->activeEnrollments as $enrollment)
                @php
                    $key = $enrollment->student_id . '-' . $class->id;
                    $log = $logs[$key] ?? null;
                    $student = $enrollment->student;

                    if ($log && ($log->check_in || $log->check_out)) {
                        $statusLabel = 'Presente';
                        $statusClass = 'badge-success';
                    } elseif ($selectedDate->lt($today)) {
                        $statusLabel = 'Falta';
                        $statusClass = 'badge-danger';
                    } elseif ($selectedDate->isFuture()) {
                        $statusLabel = 'Agendado';
                        $statusClass = 'badge-secondary';
                    } else {
                        $statusLabel = 'Sem registro';
                        $statusClass = 'badge-warning';
                    }
                @endphp
                <tr @if($statusLabel === 'Falta') style="background: #FEF2F2;" @endif>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="{{ $student->photo_url }}" 
                                 style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                            <span>{{ $student->name }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>
                        @if($log && $log->check_in)
                            <span class="badge badge-success">{{ $log->formatted_check_in }}</span>
                        @else
                            <span style="color: #9CA3AF;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($log && $log->check_out)
                            <span class="badge badge-info">{{ $log->formatted_check_out }}</span>
                        @else
                            <span style="color: #9CA3AF;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($log && $log->extra_minutes > 0)
                            <span class="badge badge-warning">{{ $log->formatted_extra_time }}</span>
                        @else
                            <span style="color: #9CA3AF;">-</span>
                        @endif
                    </td>
                    <td>{{ $log->picked_up_by ?? '-' }}</td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            @if(!$log || !$log->check_in)
                            <form action="{{ route('attendance.quick') }}" method="POST">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">
                                <input type="hidden" name="class_id" value="{{ $class->id }}">
                                <input type="hidden" name="date" value="{{ $date }}">
                                <input type="hidden" name="type" value="check_in">
                                <button type="submit" class="btn btn-success btn-sm" title="Registrar entrada">
                                    <i class="fas fa-sign-in-alt"></i>
                                </button>
                            </form>
                            @endif
                            
                            @if($log && $log->check_in && !$log->check_out)
                            <form action="{{ route('attendance.quick') }}" method="POST">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">
                                <input type="hidden" name="class_id" value="{{ $class->id }}">
                                <input type="hidden" name="date" value="{{ $date }}">
                                <input type="hidden" name="type" value="check_out">
                                <button type="submit" class="btn btn-warning btn-sm" title="Registrar saída">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                            @endif

                            @if($log)
                            <button type="button" class="btn btn-warning btn-sm" title="Reiniciar registro" 
                                onclick="openDeleteModal('{{ route('attendance.destroy', $log) }}')">
                                <i class="fas fa-redo"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="card">
    <div style="text-align: center; padding: 40px; color: #6B7280;">
        <i class="fas fa-school" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
        <p>Nenhuma turma com aula nesse dia ou sem alunos ativos.</p>
    </div>
</div>
@endforelse

{{-- Delete Modal --}}
<div id="deleteModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 20px;">
            <div style="width: 60px; height: 60px; background: #FEF3C7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                <i class="fas fa-redo" style="font-size: 24px; color: #D97706;"></i>
            </div>
            <h3 style="margin: 0 0 10px; color: #111827; font-size: 1.25rem;">Reiniciar Registro?</h3>
            <p style="margin: 0; color: #6B7280;">Isso irá limpar os horários de entrada e saída deste aluno para hoje. Deseja continuar?</p>
        </div>
        
        <form id="deleteForm" method="POST" style="display: flex; gap: 10px; justify-content: center;">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteModal()" class="btn" style="background: #E5E7EB; color: #374151; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                Cancelar
            </button>
            <button type="submit" class="btn" style="background: #D97706; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                Sim, Reiniciar
            </button>
        </form>
    </div>
</div>

<script>
function openDeleteModal(url) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    form.action = url;
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endsection
