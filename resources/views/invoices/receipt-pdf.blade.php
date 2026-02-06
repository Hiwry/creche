<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 40px 45px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111;
            font-size: 16px;
            line-height: 1.6;
        }
        .page {
            width: 100%;
            text-align: center;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 8px;
        }
        .logo img {
            max-width: 110px;
            max-height: 110px;
        }
        .company {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .divider {
            width: 70%;
            margin: 18px auto 10px;
            border-top: 1px solid #222;
        }
        .title {
            text-align: center;
            letter-spacing: 14px;
            font-size: 44px;
            font-weight: 700;
            margin: 12px 0 10px;
        }
        .value {
            text-align: center;
            font-size: 22px;
            margin: 12px 0 22px;
        }
        .value span {
            font-weight: 700;
            text-decoration: underline;
        }
        .paragraph {
            text-align: justify;
            font-size: 20px;
        }
        .date-line {
            text-align: center;
            margin-top: 32px;
            font-size: 20px;
            font-weight: 600;
        }
        .signature {
            margin-top: 36px;
            text-align: center;
        }
        .signature-image {
            max-height: 90px;
            margin: 0 auto 6px;
            display: block;
        }
        .signature-line {
            width: 300px;
            margin: 6px auto 8px;
            border-top: 1px solid #222;
        }
        .signature-name {
            font-size: 18px;
            font-weight: 700;
        }
        .footer {
            position: fixed;
            bottom: 18px;
            left: 45px;
            right: 45px;
            text-align: center;
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
@php
    $logoPath = null;
    $logoData = null;
    if (!empty($settings['company_logo'])) {
        $possiblePath = storage_path('app/public/' . $settings['company_logo']);
        if (file_exists($possiblePath)) {
            $logoPath = $possiblePath;
        }
    }
    if ($logoPath) {
        $type = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        if (in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            $logoData = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    $signaturePath = null;
    $signatureData = null;
    if (!empty($settings['company_signature'])) {
        $possiblePath = storage_path('app/public/' . $settings['company_signature']);
        if (file_exists($possiblePath)) {
            $signaturePath = $possiblePath;
        }
    }
    if ($signaturePath) {
        $type = strtolower(pathinfo($signaturePath, PATHINFO_EXTENSION));
        if (in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            $signatureData = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($signaturePath));
        }
    }

    $companyName = $settings['company_name'] ?? 'CRECHE ESCOLA';
    $guardianName = $invoice->student?->guardian?->name ?: 'Responsável';
    $studentName = $invoice->student?->name ?: 'Aluno';
    $amountFormatted = 'R$ ' . number_format((float) $invoice->total, 2, ',', '.');
    $monthName = \Illuminate\Support\Str::lower($invoice->month_name ?? '');
    $reference = trim($monthName . '/' . $invoice->year, '/');

    $city = $invoice->student?->guardian?->city;
    $state = $invoice->student?->guardian?->state;
    $place = trim(($city ?: '') . ($state ? '-' . $state : ''));
    if ($place === '') {
        $place = 'Cidade';
    }

    $issuedDate = ($issuedAt ?? now())->locale('pt_BR')->translatedFormat('d \\d\\e F \\d\\e Y');

    $amountInWords = null;
    if (class_exists(\NumberFormatter::class)) {
        $formatter = new \NumberFormatter('pt_BR', \NumberFormatter::SPELLOUT);
        $number = (float) $invoice->total;
        $integer = (int) floor($number);
        $cents = (int) round(($number - $integer) * 100);
        $words = trim($formatter->format($integer));
        $amountInWords = $words . ' reais';
        if ($cents > 0) {
            $amountInWords .= ' e ' . trim($formatter->format($cents)) . ' centavos';
        }
    }
@endphp

    <div class="page">
        <div class="header">
            @if($logoData)
                <div class="logo">
                    <img src="{{ $logoData }}" alt="Logo">
                </div>
            @endif
            <div class="company">{{ mb_strtoupper($companyName) }}</div>
            <div class="divider"></div>
        </div>

        <div class="title">RECIBO</div>
        <div class="value">VALOR: <span>{{ $amountFormatted }}</span></div>

        <div class="paragraph">
            Recebemos de <strong>{{ mb_strtoupper($guardianName) }}</strong>, a quantia de
            <strong>{{ $amountFormatted }}</strong>
            @if($amountInWords)
                ({{ $amountInWords }})
            @endif
            referente à mensalidade de <strong>{{ $reference }}</strong> para o aluno
            <strong>{{ mb_strtoupper($studentName) }}</strong>, da qual damos plena e total quitação.
        </div>

        <div class="date-line">{{ $place }}, {{ $issuedDate }}.</div>

        <div class="signature">
            @if($signatureData)
                <img src="{{ $signatureData }}" alt="Assinatura" class="signature-image">
            @endif
            <div class="signature-line"></div>
            <div class="signature-name">{{ mb_strtoupper($companyName) }}</div>
        </div>
    </div>

    <div class="footer">
        {{ $settings['company_address'] ?? '' }}
        @if(!empty($settings['company_phone']))
            | Telefone: {{ $settings['company_phone'] }}
        @endif
    </div>
</body>
</html>
