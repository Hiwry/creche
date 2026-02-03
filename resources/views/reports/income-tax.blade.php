@extends('layouts.app')

@push('styles')
<style>
    .income-tax-doc {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 30px;
        font-family: "Times New Roman", serif;
        color: #111827;
    }
    .income-tax-doc .doc-header {
        text-align: center;
        margin-bottom: 16px;
    }
    .income-tax-doc .doc-company {
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .income-tax-doc .doc-company-details {
        font-size: 0.85rem;
        margin-top: 6px;
        line-height: 1.4;
        color: #4B5563;
    }
    .income-tax-doc .doc-title {
        text-align: center;
        font-weight: 700;
        margin: 22px 0 16px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    .income-tax-doc .doc-date {
        text-align: right;
        font-size: 0.85rem;
        color: #4B5563;
        margin-bottom: 18px;
    }
    .income-tax-doc p {
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    .income-tax-doc .doc-line {
        display: flex;
        gap: 10px;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    .income-tax-doc .doc-line span.label {
        min-width: 180px;
        font-weight: 600;
    }
    .income-tax-doc .doc-line span.value {
        flex: 1;
        border-bottom: 1px solid #9CA3AF;
        padding-bottom: 2px;
    }
    .income-tax-doc table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        margin-top: 10px;
    }
    .income-tax-doc th,
    .income-tax-doc td {
        border: 1px solid #E5E7EB;
        padding: 8px 10px;
        text-align: left;
    }
    .income-tax-doc th {
        background: #F9FAFB;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.4px;
    }
    .income-tax-doc .doc-total {
        margin-top: 16px;
        font-weight: 700;
        text-align: right;
        font-size: 0.95rem;
    }
    .income-tax-doc .doc-signature {
        margin-top: 40px;
        text-align: center;
    }
    .income-tax-doc .doc-signature-line {
        width: 60%;
        margin: 0 auto 8px;
        border-top: 1px solid #111827;
    }
</style>
@endpush

@section('content')
@php
    $companyName = $settings['company_name'] ?? 'SchoolHub';
    $companyCnpj = $settings['company_cnpj'] ?? ($settings['company_document'] ?? '');
    $companyAddress = $settings['company_address'] ?? '';
    $issueDate = now();
@endphp

<div class="action-bar">
    <div class="action-bar-left">
        <a href="{{ route('students.show', $student) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 600;">Declaração de IR</h1>
        <span style="color: #6B7280;">{{ $student->name }}</span>
    </div>
    <div class="action-bar-right">
        <form action="{{ route('students.income-tax', $student) }}" method="GET" class="filter-form">
            <select name="year" class="form-control">
                @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </form>
        <a href="{{ route('students.income-tax.pdf', ['student' => $student->id, 'year' => $year]) }}" class="btn btn-primary">
            <i class="fas fa-file-pdf"></i> Baixar PDF
        </a>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 20px;">
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value">R$ {{ number_format($totalPaid, 2, ',', '.') }}</div>
            <div class="stat-label">Total pago no exercício {{ $year }}</div>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        <div class="stat-content">
            <div class="stat-value">{{ $items->count() }}</div>
            <div class="stat-label">Mensalidades consideradas</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="income-tax-doc">
        <div class="doc-header">
            <div class="doc-company">{{ $companyName }}</div>
            <div class="doc-company-details">
                @if($companyAddress)
                    {{ $companyAddress }}<br>
                @endif
                @if($companyCnpj)
                    CNPJ: {{ $companyCnpj }}
                @endif
            </div>
        </div>

        <div class="doc-title">Declaração para Imposto de Renda</div>
        <div class="doc-date">Data: {{ $issueDate->format('d/m/Y') }}</div>

        <p>
            Declaramos, para fins de comprovação perante a Secretaria da Receita Federal do Brasil, que recebemos de:
        </p>

        <div class="doc-line">
            <span class="label">Responsável Financeiro:</span>
            <span class="value">{{ $student->guardian?->name ?? '' }}</span>
        </div>
        <div class="doc-line">
            <span class="label">CPF:</span>
            <span class="value">{{ $student->guardian?->formatted_cpf ?? '' }}</span>
        </div>
        <div class="doc-line">
            <span class="label">Referente ao aluno(a):</span>
            <span class="value">{{ $student->name }}</span>
        </div>
        <div class="doc-line">
            <span class="label">Matrícula:</span>
            <span class="value">{{ $student->id }}</span>
        </div>

        <p>
            A importância total de R$ {{ number_format($totalPaid, 2, ',', '.') }}
            @if($valueInWords)
                ({{ $valueInWords }}),
            @else
                (valor por extenso),
            @endif
            paga no exercício de {{ $year }}, correspondente aos serviços educacionais prestados por esta instituição de ensino,
            conforme demonstrado abaixo:
        </p>

        <table>
            <thead>
                <tr>
                    <th style="width: 18%">Competência</th>
                    <th>Serviço</th>
                    <th style="width: 18%">Data de pagamento</th>
                    <th style="width: 18%">Valor pago (R$)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $fee = $item['fee'];
                        $competence = $fee ? str_pad($fee->month, 2, '0', STR_PAD_LEFT) . '/' . $fee->year : '-';
                        $service = 'Mensalidade - Educação Infantil (Creche)';
                        if ($fee && $fee->classModel) {
                            $service = 'Mensalidade - ' . $fee->classModel->name;
                        }
                    @endphp
                    <tr>
                        <td>{{ $competence }}</td>
                        <td>{{ $service }}</td>
                        <td>{{ $item['payment_date'] ? $item['payment_date']->format('d/m/Y') : '-' }}</td>
                        <td>R$ {{ number_format($item['amount'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color:#6B7280;">Nenhum pagamento encontrado no exercício {{ $year }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="doc-total">Total: R$ {{ number_format($totalPaid, 2, ',', '.') }}</div>

        <div class="doc-signature">
            <div class="doc-signature-line"></div>
            <strong>{{ $companyName }}</strong>
        </div>
    </div>
</div>
@endsection
