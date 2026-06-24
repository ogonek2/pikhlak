@foreach ($client->contracts as $c)
    @if ($editContract && $editContract->id === $c->id)
        @include('admin.client.clients.partials.contract-form', ['contract' => $c, 'action' => route('admin.client.clients.contracts.update', [$client, $c]), 'method' => 'PUT', 'title' => 'Редактирование', 'tab' => 'contracts'])
    @else
        <div class="group mb-3 rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
            <div class="flex items-start justify-between">
                <div>
                    @if ($c->contract_number)<div class="font-mono text-xs text-sky-400">№ {{ $c->contract_number }}</div>@endif
                    <div class="mt-1 font-medium">{{ $money($c->monthly_amount, $c->currency) }} <span class="text-slate-500 font-normal">/ {{ $c->period_weeks ?? 4 }} нед.</span></div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ $c->rent_start->format('d.m.Y') }} — {{ $c->rent_end?->format('d.m.Y') ?? '∞' }}</div>
                </div>
                <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
                    <a href="{{ route('admin.client.clients.show', [$client, 'tab' => 'contracts', 'edit_contract' => $c->id]) }}" class="text-sky-400">Изменить</a>
                    <form method="POST" action="{{ route('admin.client.clients.contracts.destroy', [$client, $c]) }}" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="text-red-400">Удалить</button></form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@if (!$editContract || !$client->contracts->contains('id', $editContract->id))
    @include('admin.client.clients.partials.contract-form', ['contract' => null, 'action' => route('admin.client.clients.contracts.store', $client), 'method' => 'POST', 'title' => 'Новый договор', 'tab' => 'contracts'])
@endif
