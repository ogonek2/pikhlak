@foreach ($client->vehicles as $v)
    @if ($editVehicle && $editVehicle->id === $v->id)
        @include('admin.client.clients.partials.vehicle-form', ['vehicle' => $v, 'action' => route('admin.client.clients.vehicles.update', [$client, $v]), 'method' => 'PUT', 'title' => 'Редактирование', 'tab' => 'vehicles'])
    @else
        <div class="group mb-3 flex items-start justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
            <div>
                <div class="font-medium">{{ $v->title() }} @if($v->is_current)<span class="text-[10px] text-sky-400">· текущий</span>@endif</div>
                @if ($v->plate_number)<div class="mt-0.5 text-slate-500">{{ $v->plate_number }}</div>@endif
                @if ($v->mileage)<div class="text-xs text-slate-600">{{ number_format($v->mileage) }} км</div>@endif
            </div>
            <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => 'vehicles', 'edit_vehicle' => $v->id]) }}" class="text-sky-400 hover:underline">Изменить</a>
                <form method="POST" action="{{ route('admin.client.clients.vehicles.destroy', [$client, $v]) }}" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="text-red-400 hover:underline">Удалить</button></form>
            </div>
        </div>
    @endif
@endforeach
@if (!$editVehicle || !$client->vehicles->contains('id', $editVehicle->id))
    @include('admin.client.clients.partials.vehicle-form', ['vehicle' => null, 'action' => route('admin.client.clients.vehicles.store', $client), 'method' => 'POST', 'title' => 'Новый автомобиль', 'tab' => 'vehicles'])
@endif
