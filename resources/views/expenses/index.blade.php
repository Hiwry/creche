@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Controle de Despesas</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ \App\Models\MonthlyFee::MONTHS[$month] ?? '' }}/{{ $year }}
        </span>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('expenses.create') }}" class="btn btn-danger">
            <i class="fas fa-plus"></i> Nova Despesa
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;">
    <div class="stat-card" style="border-left-color: #EF4444;">
        <div class="stat-icon" style="background: #FEE2E2; color: #EF4444;">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value" style="color: #EF4444;">R$ {{ number_format($monthlyTotal, 2, ',', '.') }}</div>
            <div class="stat-label">Total do Mês</div>
        </div>
    </div>
    
    @foreach(array_slice($byCategory, 0, 3, true) as $key => $cat)
    <div class="stat-card" style="border-left-color: #7C3AED;">
        <div class="stat-icon" style="background: #F5F3FF; color: #7C3AED;">
            <i class="fas fa-tag"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($cat['total'], 2, ',', '.') }}</div>
            <div class="stat-label">{{ $cat['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('expenses.index') }}" method="GET" class="filter-form">
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
        
        <select name="category" class="form-control">
            <option value="">Todas Categorias</option>
            @foreach(\App\Models\Expense::CATEGORIES as $key => $label)
            <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </form>
</div>

<!-- Expenses Table -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Forma Pgto</th>
                    <th>Valor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->formatted_date }}</td>
                    <td>
                        <div style="font-weight: 500;">{{ $expense->description }}</div>
                        @if($expense->notes)
                        <div style="font-size: 0.8rem; color: #6B7280;">{{ Str::limit($expense->notes, 50) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-secondary">{{ $expense->category_label }}</span>
                    </td>
                    <td>{{ $expense->payment_method_label }}</td>
                    <td style="font-weight: 600; color: #EF4444;">
                        {{ $expense->formatted_amount }}
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" 
                                  onsubmit="return confirm('Excluir esta despesa?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <h3>Nenhuma despesa registrada</h3>
                            <p>Clique em "Nova Despesa" para adicionar</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
        {{ $expenses->withQueryString()->links() }}
    </div>
</div>
@endsection
