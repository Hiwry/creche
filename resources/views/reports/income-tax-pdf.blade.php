<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Declaração IR</title>
    <style>
        @page { margin: 30px; }
        body {
            font-family: "Times New Roman", serif;
            font-size: 11px;
            color: #000;
            line-height: 1.5;
        }
        .doc-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .doc-company {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .doc-company-details {
            font-size: 10px;
            color: #333;
            margin-top: 4px;
        }
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 18px 0 12px;
            text-transform: uppercase;
        }
        .doc-date {
            text-align: right;
            font-size: 10px;
            color: #333;
            margin-bottom: 12px;
        }
        .doc-line {
            margin-bottom: 6px;
        }
        .doc-line span.label {
            display: inline-block;
            width: 170px;
            font-weight: bold;
        }
        .doc-line span.value {
            display: inline-block;
            border-bottom: 1px solid #555;
            min-width: 300px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #F2F2F2;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.4px;
        }
        .doc-total {
            margin-top: 12px;
            font-weight: bold;
            text-align: right;
        }
        .signature {
            margin-top: 35px;
            text-align: center;
        }
        .signature-line {
            width: 60%;
            border-top: 1px solid #000;
            margin: 0 auto 6px;
        }
    </style>
</head>
<body>
    @php
        $companyName = $settings['company_name'] ?? 'SchoolHub';
        $companyCnpj = $settings['company_cnpj'] ?? ($settings['company_document'] ?? '');
        $companyAddress = $settings['company_address'] ?? '';
    @endphp

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
    <div class="doc-date">Data: {{ ($issueDate ?? now())->format('d/m/Y') }}</div>

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
                    <td colspan="4" style="text-align:center; color:#666;">Nenhum pagamento encontrado no exercício {{ $year }}.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="doc-total">Total: R$ {{ number_format($totalPaid, 2, ',', '.') }}</div>

    <div class="signature">
        <div class="signature-line"></div>
        <strong>{{ $companyName }}</strong>
    </div>
</body>
</html>
