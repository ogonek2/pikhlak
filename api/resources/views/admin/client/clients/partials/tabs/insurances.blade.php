@foreach ($client->insurances as $ins)
    @if ($editInsurance && $editInsurance->id === $ins->id)
        @include('admin.client.clients.partials.insurance-form', ['insurance' => $ins, 'action' => route('admin.client.clients.insurances.update', [$client, $ins]), 'method' => 'PUT', 'title' => 'Редактирование', 'tab' => 'insurances'])
    @else
        <div class="group mb-3 flex justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
            <div>
                <div class="font-medium">{{ $ins->provider }}</div>
                <div class="mt-0.5 text-xs text-slate-500">{{ $ins->valid_from?->format('d.m.Y') ?? '—' }} — {{ $ins->valid_until?->format('d.m.Y') ?? '—' }}</div>
            </div>
            <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => 'insurances', 'edit_insurance' => $ins->id]) }}" class="text-sky-400">Изменить</a>
                <form method="POST" action="{{ route('admin.client.clients.insurances.destroy', [$client, $ins]) }}" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="text-red-400">Удалить</button></form>
            </div>
        </div>
    @endif
@endforeach
@if (!$editInsurance || !$client->insurances->contains('id', $editInsurance->id))
    @include('admin.client.clients.partials.insurance-form', ['insurance' => null, 'action' => route('admin.client.clients.insurances.store', $client), 'method' => 'POST', 'title' => 'Новая страховка', 'tab' => 'insurances'])
@endif
