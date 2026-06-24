<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Счёт {{ $invoiceNumber }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; padding: 12px; margin-top: 16px; }
        .row { margin-bottom: 8px; }
        .amount { font-size: 22px; font-weight: bold; }
        .qr { margin-top: 16px; text-align: center; }
    </style>
</head>
<body>
    <h1>Счёт на оплату</h1>
    <div class="muted">№ {{ $invoiceNumber }} · {{ now()->format('d.m.Y') }}</div>

    <div class="box">
        <div class="row"><strong>Клиент:</strong> {{ $client->full_name }}</div>
        <div class="row"><strong>Автомобиль:</strong> {{ $vehicleLabel }}</div>
        <div class="row"><strong>Назначение:</strong> Платёж по договору аренды</div>
        <div class="row"><strong>Срок оплаты:</strong> {{ $payment->due_date->format('d.m.Y') }}</div>
        <div class="row amount">{{ number_format((float) $payment->amount, 2, '.', ' ') }} {{ $symbol }}</div>
    </div>

    <div class="box">
        <div class="row"><strong>Оплатить онлайн:</strong></div>
        <div class="row">{{ $paymentUrl }}</div>
        @if (is_readable($qrAbsolute))
            <div class="qr">
                <img src="{{ $qrAbsolute }}" width="180" height="180" alt="QR">
            </div>
        @endif
    </div>
</body>
</html>
