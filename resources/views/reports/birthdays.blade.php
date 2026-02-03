@extends('layouts.app')

@push('styles')
<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }
    .calendar-header {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #6B7280;
        text-align: center;
        padding: 6px 0;
        background: #F3F4F6;
        border-radius: 8px;
    }
    .calendar-cell {
        min-height: 110px;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        padding: 8px;
        background: #FFFFFF;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .calendar-cell.outside {
        background: #F9FAFB;
        color: #9CA3AF;
    }
    .calendar-day {
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
    }
    .calendar-day .today {
        background: #7C3AED;
        color: #fff;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 0.7rem;
    }
    .birthday-pill {
        background: #FEF3C7;
        color: #92400E;
        font-size: 0.75rem;
        padding: 4px 6px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        gap: 6px;
    }
    .birthday-pill span.age {
        font-weight: 600;
    }
</style>
@endpush

@section('content')
@php
    $monthName = \App\Models\MonthlyFee::MONTHS[$month] ?? '';
    $firstDay = \Carbon\Carbon::create($year, $month, 1);
    $start = $firstDay->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $end = $firstDay->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $today = now();
    $weekDays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
@endphp

<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Aniversariantes do Mês</h1>
        <span style="color: #6B7280;">{{ $monthName }} / {{ $year }}</span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('reports.birthdays') }}" method="GET" class="filter-form">
            <select name="month" class="form-control">
                @foreach(\App\Models\MonthlyFee::MONTHS as $m => $name)
                <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <select name="year" class="form-control">
                @for($y = now()->year + 1; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </form>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-calendar-days" style="color: #7C3AED; margin-right: 8px;"></i>
                Calendário de {{ $monthName }}
            </h3>
            <span class="badge badge-info">{{ $students->count() }} aniversariantes</span>
        </div>

        <div class="calendar-grid" style="margin-bottom: 10px;">
            @foreach($weekDays as $label)
                <div class="calendar-header">{{ $label }}</div>
            @endforeach
        </div>

        <div class="calendar-grid">
            @php
                $cursor = $start->copy();
            @endphp
            @while($cursor->lte($end))
                @php
                    $isCurrentMonth = $cursor->month === $month;
                    $day = $cursor->day;
                    $birthdays = $isCurrentMonth ? ($birthdaysByDay[$day] ?? collect()) : collect();
                @endphp
                <div class="calendar-cell {{ $isCurrentMonth ? '' : 'outside' }}">
                    <div class="calendar-day">
                        <span>{{ $day }}</span>
                        @if($cursor->isSameDay($today))
                            <span class="today">Hoje</span>
                        @endif
                    </div>
                    @foreach($birthdays as $student)
                        @php
                            $birthDay = $student->birth_date->day;
                            $lastDay = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
                            $birthdayThisYear = \Carbon\Carbon::create($year, $month, min($birthDay, $lastDay));
                            $age = $birthdayThisYear->diffInYears($student->birth_date);
                        @endphp
                        <div class="birthday-pill">
                            <span>{{ $student->name }}</span>
                            <span class="age">{{ $age }}a</span>
                        </div>
                    @endforeach
                </div>
                @php $cursor->addDay(); @endphp
            @endwhile
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-cake-candles" style="color: #F59E0B; margin-right: 8px;"></i>
                Lista do Mês
            </h3>
        </div>

        @if($students->count() === 0)
            <div class="empty-state" style="padding: 40px 20px;">
                <i class="fas fa-cake-candles"></i>
                <p>Nenhum aniversariante encontrado.</p>
            </div>
        @else
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Data</th>
                            <th>Idade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            @php
                                $birthDay = $student->birth_date->day;
                                $lastDay = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
                                $birthdayThisYear = \Carbon\Carbon::create($year, $month, min($birthDay, $lastDay));
                                $age = $birthdayThisYear->diffInYears($student->birth_date);
                            @endphp
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->birth_date->format('d/m') }}</td>
                                <td>{{ $age }} anos</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
