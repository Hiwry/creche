@extends('layouts.app')

@section('content')
<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('financial.index', ['tab' => 'invoices']) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-left: 15px;">
            Fatura #{{ $invoice->invoice_number }}
        </h1>
        <span class="badge badge-{{ $invoice->status_color }}" style="margin-left: 10px;">
            {{ $invoice->status_label }}
        </span>
    </div>
    <div class="action-bar-right">
        @php
            $guardianEmail = $invoice->student?->guardian?->email;
        @endphp
        @if($invoice->status === 'paid')
        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-secondary">
            <i class="fas fa-print"></i> Imprimir
        </a>
        @endif
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-secondary">
            <i class="fas fa-file-pdf"></i> Baixar PDF
        </a>
        @if($invoice->status === 'paid')
        <form action="{{ route('invoices.send-receipt', $invoice) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-primary"
                    title="{{ $guardianEmail ? 'Enviar recibo por e-mail' : 'Responsável sem e-mail cadastrado' }}"
                    @disabled(!$guardianEmail)>
                <i class="fas fa-paper-plane"></i> Enviar Recibo
            </button>
        </form>
        @endif
        @if($invoice->status === 'draft')
        <form action="{{ route('invoices.recalculate', $invoice) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-info" title="Atualizar itens e valores">
                <i class="fas fa-sync"></i> Recalcular
            </button>
        </form>
        <form action="{{ route('invoices.send', $invoice) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-paper-plane"></i> Marcar como Enviada
            </button>
        </form>
        @endif
        @if($invoice->status !== 'paid')
        <form action="{{ route('invoices.paid', $invoice) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Marcar como Paga
            </button>
        </form>
        @else
        <form action="{{ route('invoices.unpaid', $invoice) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-undo"></i> Remover Pagamento
            </button>
        </form>
        @endif
    </div>
</div>

<div class="grid grid-2">
    <!-- Invoice Details -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detalhes da Fatura</h3>
        </div>
        
        <div class="grid grid-2" style="gap: 20px;">
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Aluno:</span>
                <div style="font-weight: 500;">{{ $invoice->student->name }}</div>
            </div>
            
            @if($invoice->student->guardian)
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Responsável:</span>
                <div style="font-weight: 500;">{{ $invoice->student->guardian->name }}</div>
            </div>
            @endif
            
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Referência:</span>
                <div style="font-weight: 500;">{{ $invoice->reference }}</div>
            </div>
            
            <div>
                <span style="font-size: 0.85rem; color: #6B7280;">Vencimento:</span>
                <div style="font-weight: 500;">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</div>
            </div>
        </div>
    </div>
    
    <!-- Totals -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Resumo</h3>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Subtotal:</span>
                <span>R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}</span>
            </div>
            
            @if($invoice->discount > 0)
            <div style="display: flex; justify-content: space-between; color: #10B981;">
                <span>Desconto:</span>
                <span>- R$ {{ number_format($invoice->discount, 2, ',', '.') }}</span>
            </div>
            @endif
            
            <div style="display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: 700; color: #7C3AED; padding-top: 15px; border-top: 2px solid #E5E7EB;">
                <span>Total:</span>
                <span>{{ $invoice->formatted_total }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Items -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">Itens da Fatura</h3>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Quantidade</th>
                    <th>Valor Unitário</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $item)
                <tr>
                    <td>
                        <span class="badge badge-info">{{ $item->type_label }}</span>
                    </td>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format($item->quantity, 0) }}</td>
                    <td>{{ $item->formatted_unit_price }}</td>
                    <td style="font-weight: 500;">{{ $item->formatted_total }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #9CA3AF;">
                        Nenhum item na fatura
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($invoice->notes)
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">Observações</h3>
    </div>
    <p>{{ $invoice->notes }}</p>
</div>
@endif
@endsection
