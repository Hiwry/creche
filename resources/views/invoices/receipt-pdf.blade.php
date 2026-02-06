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
            font-size: 15px;
            line-height: 1.7;
        }
        .header {
            width: 100%;
            margin-bottom: 28px;
        }
        .logo {
            float: left;
            width: 95px;
            text-align: center;
        }
        .logo img {
            max-width: 85px;
            max-height: 85px;
        }
        .company {
            margin-left: 110px;
            padding-top: 8px;
            font-size: 28px;
            font-weight: 700;
        }
        .clear { clear: both; }
        .title {
            text-align: center;
            letter-spacing: 12px;
            font-size: 48px;
            font-weight: 700;
            margin: 20px 0 8px;
        }
        .value {
            text-align: right;
            font-size: 26px;
            margin: 20px 0 24px;
        }
        .value span {
            font-weight: 700;
            text-decoration: underline;
        }
        .paragraph {
            text-align: justify;
            font-size: 24px;
        }
        .date-line {
            text-align: center;
            margin-top: 42px;
            font-size: 24px;
            font-weight: 600;
        }
        .signature {
            margin-top: 56px;
            text-align: center;
        }
        .signature-line {
            width: 280px;
            margin: 0 auto 8px;
            border-top: 1px solid #222;
        }
        .signature-name {
            font-size: 22px;
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

    <div class="header">
        <div class="logo">
            @if($logoData)
                <img src="{{ $logoData }}" alt="Logo">
            @endif
        </div>
        <div class="company">{{ mb_strtoupper($companyName) }}</div>
        <div class="clear"></div>
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
        <div class="signature-line"></div>
        <div class="signature-name">{{ mb_strtoupper($companyName) }}</div>
    </div>

    <div class="footer">
        {{ $settings['company_address'] ?? '' }}
        @if(!empty($settings['company_phone']))
            | Telefone: {{ $settings['company_phone'] }}
        @endif
    </div>
</body>
</html>
