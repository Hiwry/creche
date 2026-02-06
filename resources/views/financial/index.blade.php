@extends('layouts.app')

@section('content')
@php
    $invYear = $year ?? date('Y');
    $invMonthGenerate = ($month ?? '') !== '' ? $month : date('n');
@endphp
<div class="action-bar">
    <div class="action-bar-left">
        <h1 style="font-size: 1.5rem; font-weight: 600;">Faturas</h1>
        <span style="color: #6B7280; margin-left: 10px;">
            {{ \App\Models\Invoice::MONTHS[$month] ?? 'Todos os meses' }}/{{ $year }}
        </span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('invoices.bulk-generate') }}" method="POST" class="filter-form" style="margin: 0;">
            @csrf
            <select name="year" class="form-control">
                @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ (int) $invYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="month" class="form-control">
                @foreach(\App\Models\Invoice::MONTHS as $m => $name)
                <option value="{{ $m }}" {{ (int) $invMonthGenerate === $m ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-sync"></i> Gerar Faturas do Mês
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
            <div class="stat-label">Recebido</div>
        </div>
    </div>
    
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($summary['pending'], 2, ',', '.') }}</div>
            <div class="stat-label">Pendente</div>
        </div>
    </div>
    
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($summary['total'], 2, ',', '.') }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('financial.index') }}" method="GET" class="filter-form">
        <select name="year" class="form-control">
            @for($y = date('Y'); $y >= date('Y') - 2; $y--)
            <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        
        <select name="month" class="form-control">
            <option value="">Todos os Meses</option>
            @foreach(\App\Models\Invoice::MONTHS as $m => $name)
            <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        
        <select name="status" class="form-control">
            <option value="">Todos os Status</option>
            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Rascunho</option>
            <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Enviada</option>
            <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paga</option>
            <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Atrasada</option>
        </select>
        
        <input type="text" name="search" class="form-control" placeholder="Buscar aluno..." value="{{ $search }}">
        
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
                    <th>Turma</th>
                    <th>Valor</th>
                    <th>Pago</th>
                    <th>Status</th>
                    <th>Vencimento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                @php
                    $guardianEmail = $invoice->student?->guardian?->email;
                @endphp
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>
                        @if($invoice->student && !$invoice->student->trashed())
                            <a href="{{ route('students.show', $invoice->student) }}" class="table-link">
                                {{ $invoice->student->name }}
                            </a>
                        @elseif($invoice->student)
                            <span style="color: #9CA3AF;">{{ $invoice->student->name }} (removido)</span>
                        @else
                            <span style="color: #9CA3AF;">Aluno removido</span>
                        @endif
                    </td>
                    <td>
                        {{ $invoice->student?->activeEnrollments?->first()?->classModel?->name ?? '-' }}
                    </td>
                    <td style="font-weight: 600;">{{ $invoice->formatted_total }}</td>
                    <td>
                        @if($invoice->status === 'paid')
                            {{ $invoice->formatted_total }}
                        @else
                            R$ 0,00
                        @endif
                    </td>
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
                            @if($invoice->status === 'paid')
                            <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-secondary btn-sm" title="Imprimir recibo">
                                <i class="fas fa-print"></i>
                            </a>
                            <form action="{{ route('invoices.send-receipt', $invoice) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm"
                                        title="{{ $guardianEmail ? 'Enviar recibo por e-mail' : 'Responsável sem e-mail cadastrado' }}"
                                        @disabled(!$guardianEmail)>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                            @else
                            <button type="button" class="btn btn-secondary btn-sm" title="Recibo disponível após marcar como paga" disabled style="opacity: .6; cursor: not-allowed;">
                                <i class="fas fa-print"></i>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" title="Recibo disponível após marcar como paga" disabled style="opacity: .6; cursor: not-allowed;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            @endif
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
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <h3>Nenhuma fatura encontrada</h3>
                            <p>Use os filtros ou gere as faturas do mês</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
        {{ $invoices->appends(request()->except('page'))->links() }}
    </div>
</div>
@endsection
