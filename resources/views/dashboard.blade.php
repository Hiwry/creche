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
            <div class="stat-label">Receitas (Mês Atual)</div>
        </div>
    </div>
    
    <div class="stat-card" style="border-left-color: #EF4444; background: linear-gradient(135deg, #FEE2E2 0%, var(--bg-white) 100%);">
        <div class="stat-icon" style="background: #FEE2E2; color: #EF4444;">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value" style="color: #EF4444;">R$ {{ number_format($monthlyExpenses, 2, ',', '.') }}</div>
            <div class="stat-label">Despesas (Mês Atual)</div>
        </div>
    </div>
    
    <div class="stat-card purple">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            @php $saldo = $monthlyRevenue - $monthlyExpenses; @endphp
            <div class="stat-value" style="color: {{ $saldo >= 0 ? '#10B981' : '#EF4444' }};">
                R$ {{ number_format(abs($saldo), 2, ',', '.') }}
            </div>
            <div class="stat-label">Saldo do Mês</div>
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
    
    <!-- Expenses by Category -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Despesas por Categoria</h3>
            <a href="{{ route('expenses.index') }}" class="btn btn-secondary btn-sm">Ver Todas</a>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                @foreach($expensesByCategory as $key => $category)
                <div style="text-align: center; padding: 15px; background: #F8F9FC; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: {{ $category['total'] > 0 ? '#EF4444' : '#9CA3AF' }};">
                        R$ {{ number_format($category['total'], 2, ',', '.') }}
                    </div>
                    <div style="font-size: 0.8rem; color: #6B7280;">{{ $category['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Expenses Quick Add + Today's Schedule -->
<div class="grid grid-2">
    <!-- Quick Add Expense -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-plus-circle" style="color: #EF4444; margin-right: 10px;"></i>
                Adicionar Despesa Rápida
            </h3>
        </div>
        <div class="card-body">
            <form action="{{ route('expenses.quick') }}" method="POST">
                @csrf
                <div class="form-group">
                    <input type="text" name="description" class="form-control" placeholder="Descrição do gasto..." required>
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="category" class="form-control" required>
                            <option value="">Categoria</option>
                            @foreach(\App\Models\Expense::CATEGORIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="number" name="amount" class="form-control" placeholder="R$ 0,00" step="0.01" min="0.01" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-danger" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-plus"></i> Registrar Despesa
                </button>
            </form>
            
            <!-- Recent Expenses -->
            @if($recentExpenses->count() > 0)
            <div style="margin-top: 20px; border-top: 1px solid #E5E7EB; padding-top: 15px;">
                <div style="font-size: 0.85rem; color: #6B7280; margin-bottom: 10px;">Últimas Despesas:</div>
                @foreach($recentExpenses->take(3) as $expense)
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #F3F4F6;">
                    <div>
                        <span style="font-weight: 500;">{{ $expense->description }}</span>
                        <span class="badge badge-secondary" style="margin-left: 5px;">{{ $expense->category_label }}</span>
                    </div>
                    <span style="color: #EF4444; font-weight: 600;">{{ $expense->formatted_amount }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    
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
</div>

<!-- Recent Payments -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bell" style="color: #F59E0B; margin-right: 10px;"></i>
            Últimos Pagamentos Recebidos
        </h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Valor</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $payment)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($payment->student->name ?? 'N/A') }}&background=7C3AED&color=fff" 
                                 style="width: 35px; height: 35px; border-radius: 50%;">
                            <span>{{ $payment->student->name ?? 'N/A' }}</span>
                        </div>
                    </td>
                    <td style="font-weight: 600; color: #10B981;">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #9CA3AF;">Nenhum pagamento recente</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
