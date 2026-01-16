@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Entrada & Saída</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ \Carbon\Carbon::parse($date)->format('d/m/Y (l)') }}
        </span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('attendance.index') }}" method="GET" class="filter-form">
            <input type="date" name="date" class="form-control" value="{{ $date }}">
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-calendar"></i> Ir
            </button>
        </form>
        <a href="{{ route('attendance.extra-hours') }}" class="btn btn-warning">
            <i class="fas fa-clock"></i> Horas Extras
        </a>
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
                @endphp
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($enrollment->student->name) }}&background=7C3AED&color=fff" 
                                 style="width: 35px; height: 35px; border-radius: 50%;">
                            <span>{{ $enrollment->student->name }}</span>
                        </div>
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
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3>Nenhuma turma programada para este dia</h3>
        <p>Selecione outra data acima</p>
    </div>
</div>
@endforelse
@endsection
