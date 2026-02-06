@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Lista de Presença</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ $startDate->format('d/m/Y') }} até {{ $endDate->format('d/m/Y') }}
        </span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('attendance.report') }}" method="GET" class="filter-form" style="display: flex; gap: 10px; align-items: center;">
            <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
            <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
            <select name="class_id" class="form-control">
                <option value="">Todas as turmas</option>
                @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ (int) $selectedClassId === (int) $class->id ? 'selected' : '' }}>
                    {{ $class->name }}
                </option>
                @endforeach
            </select>
            <input type="text" name="search" class="form-control" placeholder="Buscar aluno..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </form>
        <a href="{{ route('attendance.index') }}" class="btn btn-warning">
            <i class="fas fa-clock"></i> Entrada & Saída
        </a>
    </div>
</div>

<div class="card" style="margin-bottom: 20px; padding: 12px 16px;">
    <span style="font-size: 0.85rem; color: #6B7280;">
        Faltas são calculadas com base nos dias de aula da turma sem registro de entrada.
    </span>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['students'] }}</div>
            <div class="stat-label">Alunos na Lista</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['scheduled_days'] }}</div>
            <div class="stat-label">Aulas Previstas</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['present_count'] }}</div>
            <div class="stat-label">Presenças</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-user-xmark"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['absences'] }}</div>
            <div class="stat-label">Faltas</div>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-percent"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ number_format($summary['attendance_rate'], 1, ',', '.') }}%</div>
            <div class="stat-label">Frequência Geral</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Presença por Aluno</h3>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Turma</th>
                    <th>Aulas Previstas</th>
                    <th>Presenças</th>
                    <th>Faltas</th>
                    <th>Frequência</th>
                    <th>Última Presença</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                @php
                    if ($row['attendance_rate'] >= 90) {
                        $rateClass = 'badge-success';
                    } elseif ($row['attendance_rate'] >= 75) {
                        $rateClass = 'badge-warning';
                    } else {
                        $rateClass = 'badge-danger';
                    }
                @endphp
                <tr @if($row['absences'] > 0) style="background: #FFFBEB;" @endif>
                    <td>
                        <a href="{{ route('students.show', $row['student_id']) }}" class="table-link" style="display: inline-flex; align-items: center; gap: 10px;">
                            <img src="{{ $row['student_photo_url'] }}" alt="{{ $row['student_name'] }}"
                                 style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                            <span>{{ $row['student_name'] }}</span>
                        </a>
                    </td>
                    <td>{{ $row['class_name'] }}</td>
                    <td>{{ $row['scheduled_days'] }}</td>
                    <td><span class="badge badge-success">{{ $row['present_count'] }}</span></td>
                    <td><span class="badge badge-danger">{{ $row['absences'] }}</span></td>
                    <td><span class="badge {{ $rateClass }}">{{ number_format($row['attendance_rate'], 1, ',', '.') }}%</span></td>
                    <td>
                        @if($row['last_presence_date'])
                            {{ $row['last_presence_date']->format('d/m/Y') }}
                        @else
                            <span style="color: #9CA3AF;">Sem presença</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-list-check"></i>
                            <h3>Nenhum registro encontrado</h3>
                            <p>Ajuste o período, turma ou nome do aluno para visualizar a lista.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
