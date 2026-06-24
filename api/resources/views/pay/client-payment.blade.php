<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оплата #{{ $payment->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<div class="mx-auto max-w-lg px-4 py-10">
    <h1 class="text-2xl font-bold mb-2">Оплата по договору</h1>
    <p class="text-slate-400 mb-6">{{ $client->full_name }}</p>

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-3">
        <div class="flex justify-between"><span class="text-slate-400">Сумма</span><span class="font-semibold text-xl">{{ number_format((float) $payment->amount, 2, '.', ' ') }} грн</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Срок</span><span>{{ $payment->due_date->format('d.m.Y') }}</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Счёт</span><span>{{ $invoice->invoice_number }}</span></div>
    </div>

    @if ($checkout)
        <form method="POST" action="{{ $checkout['url'] }}" accept-charset="utf-8" class="mt-6">
            <input type="hidden" name="data" value="{{ $checkout['data'] }}">
            <input type="hidden" name="signature" value="{{ $checkout['signature'] }}">
            <button type="submit" class="w-full rounded-lg bg-sky-600 py-3 font-semibold hover:bg-sky-500">
                Оплатить через LiqPay
            </button>
        </form>
    @else
        <p class="mt-6 text-sm text-slate-500">Платёжный шлюз не настроен. Укажите LIQPAY_* в .env или свяжитесь с менеджером.</p>
    @endif
</div>
</body>
</html>
