@extends('layouts.app')

@section('content')
<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card yellow">
        <div class="stat-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ $activeStudents }}</div>
            <div class="stat-label">Alunos Ativos</div>
        </div>
    </div>
    
    <div class="stat-card green">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($monthlyRevenue, 2, ',', '.') }}</div>
            <div class="stat-label">Mensalidades Pagas (Mês Atual)</div>
        </div>
    </div>
    
    <div class="stat-card orange">
        <div class="stat-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ $pendingFees }} alunos</div>
            <div class="stat-label">Pendências</div>
        </div>
    </div>
    
    <div class="stat-card purple">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($extraHoursData->total_charge ?? 0, 2, ',', '.') }}</div>
            <div class="stat-label">Horas Extras (Total do Mês)</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-grid">
    <!-- Payment Status Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Status dos Pagamentos</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: center; gap: 30px;">
                <div style="position: relative; width: 150px; height: 150px;">
                    <svg viewBox="0 0 36 36" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="#E5E7EB" stroke-width="3"></path>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="#7C3AED" stroke-width="3"
                              stroke-dasharray="{{ $paymentStatusData['paid_percentage'] }}, 100"></path>
                    </svg>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #7C3AED;">{{ $paymentStatusData['paid_percentage'] }}%</div>
                        <div style="font-size: 0.75rem; color: #6B7280;">{{ $paymentStatusData['paid'] }} alunos</div>
                    </div>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="width: 12px; height: 12px; background: #7C3AED; border-radius: 50%;"></span>
                        <span style="font-size: 0.9rem; color: #6B7280;">{{ $paymentStatusData['paid_percentage'] }}% Pagos</span>
                        <span style="font-weight: 600;">{{ $paymentStatusData['paid'] }} alunos</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 12px; height: 12px; background: #E5E7EB; border-radius: 50%;"></span>
                        <span style="font-size: 0.9rem; color: #6B7280;">{{ $paymentStatusData['pending_percentage'] }}% Pendentes</span>
                        <span style="font-weight: 600;">{{ $paymentStatusData['pending'] }} alunos</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Extra Hours Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Horas Extras</h3>
            <span style="font-size: 0.8rem; color: #6B7280;">Últimos 7 dias</span>
        </div>
        <div class="card-body">
            <div style="display: flex; align-items: flex-end; gap: 15px; height: 150px; padding-top: 20px;">
                @foreach($extraHoursChartData as $day)
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                    <div style="width: 100%; background: {{ $loop->last ? '#FFE066' : '#7C3AED' }}; 
                                border-radius: 6px 6px 0 0;
                                height: {{ max(5, min(100, $day['hours'] * 20)) }}px;
                                transition: all 0.3s ease;">
                    </div>
                    <span style="font-size: 0.75rem; color: #6B7280; margin-top: 8px;">{{ $day['day'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Today's Schedule and Recent Activities -->
<div class="grid grid-2">
    <!-- Today's Schedule -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-calendar-day" style="color: #7C3AED; margin-right: 10px;"></i>
                Agenda de Hoje - {{ now()->format('d/m') }}
            </h3>
        </div>
        <div class="card-body">
            @forelse($todaySchedule as $class)
            <div style="display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                <span style="font-weight: 600; color: #7C3AED; min-width: 50px;">
                    {{ \Carbon\Carbon::parse($class->start_time)->format('H:i') }}
                </span>
                <span>{{ $class->name }}</span>
            </div>
            @empty
            <div class="empty-state" style="padding: 30px;">
                <i class="fas fa-calendar-times"></i>
                <p>Nenhuma turma programada para hoje</p>
            </div>
            @endforelse
            
            @if($todaySchedule->count() > 0)
            <div style="text-align: center; margin-top: 15px;">
                <a href="{{ route('attendance.index') }}" class="btn btn-warning btn-sm">Ver Mais</a>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-bell" style="color: #F59E0B; margin-right: 10px;"></i>
                Últimos Pagamentos
            </h3>
        </div>
        <div class="card-body">
            @forelse($recentPayments as $payment)
            <div style="display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #E5E7EB;">
                <img src="{{ $payment->student->guardian->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($payment->student->name) }}" 
                     alt="" style="width: 40px; height: 40px; border-radius: 50%;">
                <div style="flex: 1;">
                    <div style="font-weight: 500;">{{ $payment->student->name }}</div>
                    <div style="font-size: 0.8rem; color: #6B7280;">Pagamento confirmado</div>
                </div>
                <span style="font-size: 0.75rem; color: #9CA3AF;">
                    {{ $payment->created_at->diffForHumans() }}
                </span>
            </div>
            @empty
            <div class="empty-state" style="padding: 30px;">
                <i class="fas fa-receipt"></i>
                <p>Nenhum pagamento recente</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
