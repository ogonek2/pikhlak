<div class="overflow-hidden rounded-xl border border-slate-800/80">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-950/60 text-[10px] font-medium uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Дата</th>
                <th class="px-4 py-3">Тип</th>
                <th class="px-4 py-3">Сумма</th>
                <th class="px-4 py-3">Статус</th>
                <th class="px-4 py-3 text-right"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
        @forelse ($client->payments as $p)
            <tr class="hover:bg-slate-950/30">
                <td class="px-4 py-3 text-slate-300">{{ $p->due_date->format('d.m.Y') }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $paymentTypes[$p->type] ?? $p->type }}</td>
                <td class="px-4 py-3 font-medium">{{ number_format($p->amount, 0) }}</td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $p->status === 'overdue' ? 'bg-red-500/10 text-red-400' : ($p->status === 'paid' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400') }}">
                        {{ $paymentStatuses[$p->status] ?? $p->status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right text-xs whitespace-nowrap">
                    @if ($p->status !== 'paid')
                    <form class="inline" method="POST" action="{{ route('admin.client.clients.payments.paid', [$client, $p]) }}">@csrf<button class="text-emerald-400 hover:underline">✓</button></form>
                    @endif
                    <a href="{{ route('admin.client.clients.show', [$client, 'tab' => 'payments', 'edit_payment' => $p->id]) }}" class="ml-2 text-sky-400 hover:underline">Изм.</a>
                    <form class="inline ml-1" method="POST" action="{{ route('admin.client.clients.payments.destroy', [$client, $p]) }}" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="text-red-400/70 hover:text-red-400">×</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-600">Платежей нет</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">
    @if ($editPayment)
        @include('admin.client.clients.partials.payment-form', ['payment' => $editPayment, 'action' => route('admin.client.clients.payments.update', [$client, $editPayment]), 'method' => 'PUT', 'title' => 'Редактирование', 'tab' => 'payments'])
    @else
        @include('admin.client.clients.partials.payment-form', ['payment' => null, 'action' => route('admin.client.clients.payments.store', $client), 'method' => 'POST', 'title' => 'Новый платёж', 'tab' => 'payments'])
    @endif
</div>
