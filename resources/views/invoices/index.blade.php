@extends('layouts.app')

@section('content')
@php
    $bulkYear = request('year', date('Y'));
    $bulkMonth = request('month');
    if (!$bulkMonth) {
        $bulkMonth = date('n');
    }
@endphp
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Faturas</h1>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('invoices.bulk-generate') }}" method="POST" class="filter-form" style="margin: 0;">
            @csrf
            <select name="year" class="form-control">
                @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ (int) $bulkYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="month" class="form-control">
                @foreach(\App\Models\Invoice::MONTHS as $m => $name)
                <option value="{{ $m }}" {{ (int) $bulkMonth === $m ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-sync"></i> Gerar Faturas do Mês
            </button>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('invoices.index') }}" method="GET" class="filter-form">
        <select name="year" class="form-control">
            @for($y = date('Y'); $y >= date('Y') - 2; $y--)
            <option value="{{ $y }}" {{ request('year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        
        <select name="month" class="form-control">
            <option value="">Todos os Meses</option>
            @foreach(\App\Models\Invoice::MONTHS as $m => $name)
            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        
        <select name="status" class="form-control">
            <option value="">Todos os Status</option>
            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Rascunho</option>
            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Enviada</option>
            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paga</option>
            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Atrasada</option>
        </select>
        
        <input type="text" name="search" class="form-control" placeholder="Buscar aluno..." value="{{ request('search') }}">
        
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </form>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Aluno</th>
                    <th>Referência</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Vencimento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>
                        <div style="font-weight: 500;">{{ $invoice->student->name }}</div>
                    </td>
                    <td>{{ $invoice->reference }}</td>
                    <td style="font-weight: 600;">{{ $invoice->formatted_total }}</td>
                    <td>
                        <span class="badge badge-{{ $invoice->status_color }}">
                            {{ $invoice->status_label }}
                        </span>
                    </td>
                    <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary btn-sm" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-secondary btn-sm" title="PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            @if($invoice->status !== 'paid')
                            <form action="{{ route('invoices.paid', $invoice) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" title="Marcar como paga">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @else
                            <form action="{{ route('invoices.unpaid', $invoice) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm" title="Remover pagamento">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <h3>Nenhuma fatura encontrada</h3>
                            <p>Clique em "Gerar Faturas do Mês" para criar</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
        {{ $invoices->withQueryString()->links() }}
    </div>
</div>
@endsection
