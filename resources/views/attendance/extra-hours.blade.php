@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Relatório de Horas Extras</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ \App\Models\MonthlyFee::MONTHS[$month] ?? '' }}/{{ $year }}
        </span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('attendance.extra-hours') }}" method="GET" class="filter-form">
            <select name="year" class="form-control">
                @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="month" class="form-control">
                @foreach(\App\Models\MonthlyFee::MONTHS as $m => $name)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ floor($summary['total_minutes'] / 60) }}h {{ $summary['total_minutes'] % 60 }}min</div>
            <div class="stat-label">Total de Horas Extras</div>
        </div>
    </div>
    
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($summary['total_charge'], 2, ',', '.') }}</div>
            <div class="stat-label">Valor Total a Cobrar</div>
        </div>
    </div>
    
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ $summary['students_count'] }}</div>
            <div class="stat-label">Alunos com Horas Extras</div>
        </div>
    </div>
</div>

<!-- By Student -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Horas Extras por Aluno</h3>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Dias</th>
                    <th>Total Minutos</th>
                    <th>Total Horas</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byStudent as $data)
                <tr>
                    <td>
                        <div style="font-weight: 500;">{{ $data['student']->name }}</div>
                    </td>
                    <td>{{ $data['days'] }} dia(s)</td>
                    <td>{{ $data['total_minutes'] }} min</td>
                    <td>{{ floor($data['total_minutes'] / 60) }}h {{ $data['total_minutes'] % 60 }}min</td>
                    <td>
                        <span style="font-weight: 600; color: #10B981;">
                            R$ {{ number_format($data['total_charge'], 2, ',', '.') }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <h3>Nenhuma hora extra registrada</h3>
                            <p>Não há registros de horas extras para este período</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
