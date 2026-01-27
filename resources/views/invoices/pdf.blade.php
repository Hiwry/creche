<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fatura {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 30px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }
        .header-top {
            width: 100%;
            text-align: right;
            margin-bottom: 5px;
        }
        .issued-box {
            display: inline-block;
            border: 1px dotted #999;
            padding: 3px 10px;
            font-size: 9px;
            margin-left: 5px;
        }
        .invoice-title {
            text-align: right;
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info-bar {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-item {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
            padding: 0 5px;
        }
        .info-box {
            border: 1px dotted #999;
            padding: 5px;
            border-radius: 5px;
            background-color: #fff;
        }
        .info-label {
            display: inline-block;
            margin-right: 5px;
            color: #666;
        }
        .info-value {
            font-weight: bold;
            font-size: 12px;
        }
        .rounded-box {
            border: 1px dotted #999;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .company-header {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .company-details {
            text-align: center;
            font-size: 10px;
            line-height: 1.3;
        }
        .logo-container {
            position: absolute;
            left: 15px;
            top: 15px;
            width: 80px;
        }
        .client-box-header {
            margin-bottom: 5px;
            font-size: 11px;
        }
        .client-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .client-table td {
            padding: 2px 5px;
            border-bottom: 1px dashed #ccc;
        }
        .client-table td:first-child {
            width: 70px;
            font-weight: bold;
            background-color: #fcece4; /* Light orange tint for label bg if needed, or just keep white */
        }
        .items-container {
            border: 1px solid #000;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            background-color: #F4A460; /* SandyBrown */
            border: 1px solid #000;
            padding: 5px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border: 1px dashed #000; /* Vertical lines solid, horizontal dashed? Image shows dashed horizontal */
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 5px;
            font-size: 10px;
        }
        .items-table td.center { text-align: center; }
        .items-table td.right { text-align: right; }
        
        .discount-row {
            background-color: #ffff00;
        }
        .total-row td {
            background-color: #F4A460;
            font-weight: bold;
            border-top: 2px solid #000;
            text-align: right;
            padding: 5px 10px;
        }
        .terms-text {
            font-size: 9px;
            line-height: 1.4;
            margin-bottom: 20px;
        }
        .bottom-section {
            display: table;
            width: 100%;
        }
        .payment-box {
            display: table-cell;
            width: 45%;
            border: 1px dotted #999;
            border-radius: 15px;
            padding: 10px;
            vertical-align: top;
            font-size: 9px;
        }
        .signature-box {
            display: table-cell;
            width: 55%;
            vertical-align: bottom;
            text-align: center;
            padding-left: 20px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto 5px auto;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="header-top">
        <span class="issued-box">
            Emitido em: {{ now()->format('d-m-Y H:i:s') }}
        </span>
    </div>
    <div class="invoice-title">FATURA</div>

    <!-- Info Bar -->
    <div class="info-bar">
        <div class="info-item" style="width: 25%;">
            <div class="info-box">
                <span class="info-label">Número:</span>
                <span class="info-value">{{ $invoice->month }}/{{ $invoice->year }}</span>
            </div>
        </div>
        <div class="info-item" style="width: 25%;">
            <div class="info-box">
                <span class="info-label">Valor:</span>
                <span class="info-value">R$ {{ number_format($invoice->total, 2, ',', '.') }}</span>
            </div>
        </div>
        <div class="info-item" style="width: 25%;">
            <div class="info-box">
                <span class="info-label">Vencimento:</span>
                <span class="info-value">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Company Info -->
    <div class="rounded-box">
        <div class="logo-container">
            @php
                $logoPath = null;
                $logoData = null;
                
                if (isset($settings['company_logo']) && $settings['company_logo']) {
                    $possiblePath = storage_path('app/public/' . $settings['company_logo']);
                    if (file_exists($possiblePath)) {
                        $logoPath = $possiblePath;
                    }
                }

                if ($logoPath) {
                    try {
                        $type = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                        
                        // DomPDF has issues with some formats (like webp). Let's restrict to safe ones.
                        if (in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                            // Use absolute path for DomPDF (more memory efficient and stable than base64)
                            $logoData = 'file://' . str_replace('\\', '/', $logoPath);
                        } else {
                            $logoData = null;
                        }
                    } catch (\Throwable $e) {
                        $logoData = null;
                    }
                }
            @endphp

            @if($logoData)
                <img src="{{ $logoData }}" style="width: 80px; height: auto;">
            @else
                <!-- Fallback to empty or default if needed -->
                <div style="width: 60px; height: 60px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 8px;">
                    Sem Logo
                </div>
            @endif
        </div>
        <div class="company-header">{{ $settings['company_name'] ?? 'CRECHE ESCOLA DONA CORUJA LTDA' }}</div>
        <div class="company-details">
            {{ $settings['company_address'] ?? 'Endereco da Escola' }}<br>
            CNPJ: {{ $settings['company_document'] ?? '00.000.000/0000-00' }}<br>
            Tel: {{ $settings['company_phone'] ?? '(00) 0000-0000' }}<br>
            Email: {{ $settings['company_email'] ?? 'contato@escola.com' }}
        </div>
    </div>

    <!-- Client Info -->
    <div class="rounded-box" style="padding: 10px;">
        <div class="client-box-header">
            <strong>Cliente:</strong> {{ $invoice->student->name ?? 'Aluno Removido' }}
        </div>
        <table class="client-table">
            <tr>
                <td style="background-color: #f4caa6;">Nome:</td>
                <td style="background-color: #f4caa6;">{{ $invoice->student->guardian->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Endereço:</td>
                <td>{{ $invoice->student->guardian->full_address ?? 'Endereço não cadastrado' }}</td>
            </tr>
            <tr>
                <td>Local:</td>
                <td>
                    @if($invoice->student && $invoice->student->guardian)
                        {{ $invoice->student->guardian->city ?? '' }}-{{ $invoice->student->guardian->state ?? '' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Telefone:</td>
                <td>
                    @if($invoice->student && $invoice->student->guardian)
                        {{ $invoice->student->guardian->formatted_phone ?? $invoice->student->guardian->phone ?? '' }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <div class="items-container">
        <table class="items-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 50px;">ITEM</th>
                    <th rowspan="2" style="width: 60px;">QUANT.</th>
                    <th rowspan="2">DESCRIÇÃO</th>
                    <th colspan="2">VALOR</th>
                </tr>
                <tr>
                    <th style="width: 80px;">UNITARIO</th>
                    <th style="width: 80px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php $counter = 1; @endphp
                @foreach($invoice->items as $item)
                <tr>
                    <td class="center">{{ str_pad($counter++, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
                @endforeach

                <!-- If there is a global discount not applied as an item, show it here -->
                @if($invoice->discount > 0)
                <tr class="discount-row">
                    <td class="center">{{ str_pad($counter++, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="center">1</td>
                    <td>Desconto</td>
                    <td class="right">-</td>
                    <td class="right">- {{ number_format($invoice->discount, 2, ',', '.') }}</td>
                </tr>
                @endif
                
                <!-- Filler rows to maintain height if needed, otherwise just total -->
                
                <tr class="total-row">
                    <td colspan="4" style="text-align: right; border-right: none;">TOTAL R$</td>
                    <td style="border-left: none;">{{ number_format($invoice->total, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Terms -->
    <div class="terms-text">
        Após o vencimento cobrar MULTA de {{ $settings['fine_percent'] ?? '2' }}% ........ R$ {{ number_format($invoice->total * 0.02, 2, ',', '.') }}<br>
        Após o vencimento cobrar JUROS de {{ $settings['interest_percent'] ?? '1' }}% ao mês ........ R$ {{ number_format($invoice->total * 0.01, 2, ',', '.') }}<br>
        Após o vencimento cobrar ATUALIZAÇÃO MONETÁRIA de 0,033% ao dia.<br>
        <strong>Senhores Pais ou Responsáveis,</strong><br>
        Solicitamos que o pagamento seja realizado de acordo com o valor informado na fatura. Em caso de dúvidas,
        por favor, contatar a secretária para emissão de novos valores ou verificações de alterações necessárias.<br>
        Informamos que não recebemos pagamentos parciais.<br>
        <br>
        <strong>Por favor enviar comprovante pelo WhatsApp: {{ $settings['company_whatsapp'] ?? $settings['company_phone'] ?? '' }}</strong>
    </div>

    <!-- Bottom Section -->
    <div class="bottom-section">
        <div class="payment-box">
            <strong>Formas de pagamento:</strong><br><br>
            <strong>Pix:</strong> {{ $settings['pix_key'] ?? $settings['company_document'] ?? 'CNPJ/CPF da Chave' }}<br>
            <strong>Banco:</strong> {{ $settings['bank_name'] ?? 'Banco Exemplo' }}<br>
            {{ $settings['company_name'] ?? 'Nome do Titular' }}<br>
            <br>
            <strong>Espécie</strong><br>
            Cartão de crédito/Débitos (não será concedido desconto).
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>{{ $settings['company_name'] ?? 'Escola' }}</strong><br>
            Departamento Financeiro
        </div>
    </div>

</body>
</html>
