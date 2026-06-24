@foreach ($client->maintenances as $m)
    @if ($editMaintenance && $editMaintenance->id === $m->id)
        @include('admin.client.clients.partials.maintenance-form', ['maintenance' => $m, 'action' => route('admin.client.clients.maintenances.update', [$client, $m]), 'method' => 'PUT', 'title' => 'Редактирование', 'tab' => 'maintenances'])
    @else
        <div class="group mb-3 flex justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
            <div>
                <div class="font-medium">{{ $m->title }}</div>
                <div class="mt-0.5 text-xs text-slate-500">{{ $maintenanceTypes[$m->type] ?? $m->type }} · {{ $m->scheduled_at?->format('d.m.Y') ?? '—' }}</div>
            </div>
            <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => 'maintenances', 'edit_maintenance' => $m->id]) }}" class="text-sky-400">Изменить</a>
                <form method="POST" action="{{ route('admin.client.clients.maintenances.destroy', [$client, $m]) }}" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="text-red-400">Удалить</button></form>
            </div>
        </div>
    @endif
@endforeach
@if (!$editMaintenance || !$client->maintenances->contains('id', $editMaintenance->id))
    @include('admin.client.clients.partials.maintenance-form', ['maintenance' => null, 'action' => route('admin.client.clients.maintenances.store', $client), 'method' => 'POST', 'title' => 'Новая запись', 'tab' => 'maintenances'])
@endif
