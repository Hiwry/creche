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

<!-- Summary & Quick Add Row -->
<div class="grid grid-2" style="margin-bottom: 20px; align-items: start;">
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
        </div>
    </div>
    
    <!-- Expenses by Category -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Resumo por Categoria</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                @foreach($byCategory as $key => $category)
                <div style="padding: 12px; background: #F8F9FC; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: #6B7280;">{{ $category['label'] }}:</span>
                    <span style="font-weight: 600; color: {{ $category['total'] > 0 ? '#EF4444' : '#9CA3AF' }};">
                        R$ {{ number_format($category['total'], 2, ',', '.') }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('expenses.index') }}" method="GET" class="filter-form">
        <div style="display: flex; gap: 15px; align-items: center;">
            <div style="font-weight: 500; color: #374151;">Filtrar Período:</div>
            <select name="year" class="form-control" style="width: auto;">
                @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            
            <select name="month" class="form-control" style="width: auto;">
                @foreach(\App\Models\MonthlyFee::MONTHS as $m => $name)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            
            <select name="category" class="form-control" style="width: auto;">
                <option value="">Todas Categorias</option>
                @foreach(\App\Models\Expense::CATEGORIES as $key => $label)
                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Aplicar
            </button>
        </div>
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
