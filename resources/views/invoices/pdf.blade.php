<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fatura {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #7C3AED;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #7C3AED;
        }
        .company-info {
            font-size: 11px;
            color: #666;
            margin-top: 10px;
        }
        .invoice-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-info-left, .invoice-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .invoice-info-right {
            text-align: right;
        }
        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #7C3AED;
        }
        .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .value {
            font-weight: bold;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #7C3AED;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-size: 16px;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #333;
            padding-top: 15px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-paid { background: #D1FAE5; color: #10B981; }
        .status-pending { background: #FEF3C7; color: #F59E0B; }
        .status-overdue { background: #FEE2E2; color: #EF4444; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">SchoolHub</div>
        <div class="company-info">
            {{ $settings['company_name'] ?? 'SchoolHub' }}<br>
            {{ $settings['company_address'] ?? '' }}<br>
            {{ $settings['company_phone'] ?? '' }} | {{ $settings['company_email'] ?? '' }}
        </div>
    </div>
    
    <!-- Invoice Info -->
    <div class="invoice-info">
        <div class="invoice-info-left">
            <div class="label">Faturado Para:</div>
            <div class="value">{{ $invoice->student->name }}</div>
            @if($invoice->student->guardian)
            <div style="margin-top: 5px;">
                Responsável: {{ $invoice->student->guardian->name }}<br>
                {{ $invoice->student->guardian->email ?? '' }}
            </div>
            @endif
        </div>
        <div class="invoice-info-right">
            <div class="invoice-number">Fatura #{{ $invoice->invoice_number }}</div>
            <div style="margin-top: 10px;">
                <div class="label">Referência:</div>
                <div class="value">{{ $invoice->reference }}</div>
            </div>
            <div style="margin-top: 10px;">
                <div class="label">Vencimento:</div>
                <div class="value">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</div>
            </div>
            <div style="margin-top: 10px;">
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ $invoice->status_label }}
                </span>
            </div>
        </div>
    </div>
    
    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Descrição</th>
                <th class="text-right">Qtd</th>
                <th class="text-right">Valor Unit.</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                <td class="text-right">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            
            <!-- Subtotal -->
            <tr>
                <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                <td class="text-right">R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            
            @if($invoice->discount > 0)
            <tr>
                <td colspan="3" class="text-right">Desconto:</td>
                <td class="text-right" style="color: #10B981;">- R$ {{ number_format($invoice->discount, 2, ',', '.') }}</td>
            </tr>
            @endif
            
            <!-- Total -->
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-right" style="color: #7C3AED;">R$ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    
    @if($invoice->notes)
    <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
        <strong>Observações:</strong><br>
        {{ $invoice->notes }}
    </div>
    @endif
    
    <!-- Footer -->
    <div class="footer">
        Documento gerado em {{ now()->format('d/m/Y H:i') }}<br>
        {{ $settings['company_name'] ?? 'SchoolHub' }} - Sistema de Gestão de Alunos
    </div>
</body>
</html>
