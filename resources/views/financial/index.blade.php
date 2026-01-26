@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Financeiro - Mensalidades</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ \App\Models\MonthlyFee::MONTHS[$month] ?? '' }}/{{ $year }}
        </span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('financial.generate-monthly-fees') }}" method="POST" style="display: inline;">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-sync"></i> Gerar Mensalidades
            </button>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($summary['paid'], 2, ',', '.') }}</div>
            <div class="stat-label">Recebido ({{ $summary['paid_count'] }} pagos)</div>
        </div>
    </div>
    
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($summary['pending'], 2, ',', '.') }}</div>
            <div class="stat-label">Pendente ({{ $summary['pending_count'] }} pendentes)</div>
        </div>
    </div>
    
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($summary['total'], 2, ',', '.') }}</div>
            <div class="stat-label">Total Esperado</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('financial.index') }}" method="GET" class="filter-form">
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
        
        <select name="status" class="form-control">
            <option value="">Todos os Status</option>
            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Pago</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendente</option>
            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Atrasado</option>
        </select>
        
        <input type="text" name="search" class="form-control" placeholder="Buscar aluno..." value="{{ request('search') }}">
        
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </form>
</div>

<!-- Monthly Fees Table -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Turma</th>
                    <th>Valor</th>
                    <th>Pago</th>
                    <th>Status</th>
                    <th>Vencimento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthlyFees as $fee)
                <tr>
                    <td>
                        <div style="font-weight: 500;">{{ $fee->student->name }}</div>
                    </td>
                    <td>{{ $fee->classModel->name ?? '-' }}</td>
                    <td>R$ {{ number_format($fee->net_amount, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($fee->amount_paid, 2, ',', '.') }}</td>
                    <td>
                        <span class="badge badge-{{ $fee->status_color }}">
                            {{ $fee->status_label }}
                        </span>
                    </td>
                    <td>{{ $fee->due_date ? $fee->due_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        @if($fee->status !== 'paid')
                        <form action="{{ route('financial.mark-paid', ['type' => 'monthly_fee', 'id' => $fee->id]) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" title="Marcar como pago">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        @else
                        <span class="badge badge-success"><i class="fas fa-check"></i> Pago</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <h3>Nenhuma mensalidade encontrada</h3>
                            <p>Clique em "Gerar Mensalidades" para criar</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
        {{ $monthlyFees->withQueryString()->links() }}
    </div>
</div>
@endsection
