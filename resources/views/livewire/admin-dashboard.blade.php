<?php

use Livewire\Volt\Component;
use App\Models\Ticket;
use App\Models\Category;
use App\Models\User;

new class extends Component {
    public $totalTickets;
    public $openTickets;
    public $inProgressTickets;
    public $resolvedTickets;
    public $ticketsByCategory;
    public $ticketsByPriority;
    public $recentTickets;
    public $allTickets;
    public $assignableTickets;
    public $agents;
    public $selectedTicketId;

    /**
     * Cada elemento es un user id (string) o '' para la fila vacía al final.
     * Siempre termina en '' para permitir añadir otro agente.
     *
     * @var list<string>
     */
    public array $pickedAgentSlots = [''];

    public function mount()
    {
        $this->loadStatistics();
    }

    public function updatedSelectedTicketId(): void
    {
        $this->pickedAgentSlots = [''];
    }

    public function updatedPickedAgentSlots(): void
    {
        $ordered = [];
        foreach ($this->pickedAgentSlots as $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $id = (int) $v;
            if (!in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        $this->pickedAgentSlots = array_map(static fn(int $id): string => (string) $id, $ordered);

        $maxAgents = collect($this->agents ?? [])->count();

        if ($maxAgents === 0) {
            $this->pickedAgentSlots = [''];

            return;
        }

        if (count($this->pickedAgentSlots) < $maxAgents) {
            $this->pickedAgentSlots[] = '';
        }
    }

    public function loadStatistics()
    {
        $this->totalTickets = Ticket::count();
        $this->openTickets = Ticket::where('status', 'abierto')->count();
        $this->inProgressTickets = Ticket::where('status', 'en_proceso')->count();
        $this->resolvedTickets = Ticket::where('status', 'resuelto')->count();

        $this->ticketsByCategory = Category::withCount('tickets')
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'count' => $category->tickets_count,
                    'color' => $category->color,
                ];
            });

        $this->ticketsByPriority = [
            'baja' => Ticket::where('priority', 'baja')->count(),
            'media' => Ticket::where('priority', 'media')->count(),
            'alta' => Ticket::where('priority', 'alta')->count(),
            'urgente' => Ticket::where('priority', 'urgente')->count(),
        ];

        $this->recentTickets = Ticket::with(['user', 'category'])
            ->latest()
            ->take(5)
            ->get();

        $this->allTickets = Ticket::with(['user', 'category', 'assignedTo', 'assignedAgents'])
            ->latest()
            ->get();

        $this->assignableTickets = Ticket::assignable()
            ->with(['assignedAgents'])
            ->latest()
            ->get();

        $this->agents = User::whereHas('roles', function ($query) {
            $query->whereIn('slug', ['agent', 'admin']);
        })->get();
    }

    public function assignTicket()
    {
        $selectedAgentIds = collect($this->pickedAgentSlots)->filter(fn($v) => $v !== '' && $v !== null)->map(fn($v) => (int) $v)->unique()->values()->all();

        $this->validate([
            'selectedTicketId' => 'required|exists:tickets,id',
        ]);

        if (count($selectedAgentIds) < 1) {
            $this->addError('pickedAgentSlots', 'Selecciona al menos un agente.');

            return;
        }

        $ticket = Ticket::findOrFail($this->selectedTicketId);

        if (!$ticket->isAssignable()) {
            $this->addError('selectedTicketId', 'Solo se pueden asignar tickets abiertos o en proceso.');

            return;
        }

        $validAgentIds = User::query()
            ->whereIn('id', $selectedAgentIds)
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['agent', 'admin']);
            })
            ->pluck('id');

        if ($validAgentIds->isEmpty()) {
            session()->flash('message', 'Selecciona al menos un usuario con rol de agente o administrador.');

            return;
        }

        $beforeIds = $ticket->assignedAgentUserIds();

        $ticket->assignedAgents()->syncWithoutDetaching($validAgentIds->all());

        if ($ticket->status === 'abierto') {
            $ticket->update(['status' => 'en_proceso']);
        }

        $ticket->refreshPrimaryAssigneeFromPivot();

        $afterIds = $ticket->assignedAgentUserIds();
        $newIds = array_values(array_diff($afterIds, $beforeIds));

        foreach (User::whereIn('id', $newIds)->get() as $agent) {
            $agent->notify(new \App\Notifications\TicketAssigned($ticket));
        }

        $this->selectedTicketId = null;
        $this->pickedAgentSlots = [''];

        $this->loadStatistics();

        session()->flash('message', 'Agentes asignados al ticket correctamente.');
    }

    public function with()
    {
        return [
            'totalTickets' => $this->totalTickets,
            'openTickets' => $this->openTickets,
            'inProgressTickets' => $this->inProgressTickets,
            'resolvedTickets' => $this->resolvedTickets,
            'ticketsByCategory' => $this->ticketsByCategory,
            'ticketsByPriority' => $this->ticketsByPriority,
            'recentTickets' => $this->recentTickets,
            'allTickets' => $this->allTickets,
            'assignableTickets' => $this->assignableTickets,
            'agents' => $this->agents,
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Dashboard de Tickets</h2>
        <p class="mt-1 text-sm text-gray-600">Estadísticas del sistema de soporte</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Tickets</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalTickets }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Abiertos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $openTickets }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">En Proceso</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $inProgressTickets }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Resueltos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $resolvedTickets }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Tickets by Category -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Tickets por Categoría</h3>
            @if ($ticketsByCategory->count() > 0)
                <div class="space-y-3">
                    @foreach ($ticketsByCategory as $category)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full mr-2"
                                    style="background-color: {{ $category['color'] }}"></div>
                                <span class="text-sm text-gray-700">{{ $category['name'] }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $category['count'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full"
                                style="background-color: {{ $category['color'] }}; width: {{ ($category['count'] / $totalTickets) * 100 }}%">
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No hay datos disponibles</p>
            @endif
        </div>

        <!-- Tickets by Priority -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Tickets por Prioridad</h3>
            @if ($totalTickets > 0)
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Urgente</span>
                        <span class="text-sm font-medium text-red-600">{{ $ticketsByPriority['urgente'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-red-500"
                            style="width: {{ ($ticketsByPriority['urgente'] / $totalTickets) * 100 }}%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Alta</span>
                        <span class="text-sm font-medium text-orange-600">{{ $ticketsByPriority['alta'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-orange-500"
                            style="width: {{ ($ticketsByPriority['alta'] / $totalTickets) * 100 }}%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Media</span>
                        <span class="text-sm font-medium text-yellow-600">{{ $ticketsByPriority['media'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-yellow-500"
                            style="width: {{ ($ticketsByPriority['media'] / $totalTickets) * 100 }}%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Baja</span>
                        <span class="text-sm font-medium text-green-600">{{ $ticketsByPriority['baja'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-green-500"
                            style="width: {{ ($ticketsByPriority['baja'] / $totalTickets) * 100 }}%"></div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500">No hay datos disponibles</p>
            @endif
        </div>
    </div>

    <!-- Recent Tickets -->
    {{-- <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Tickets Recientes</h3>
        </div>
        @if ($recentTickets->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach ($recentTickets as $ticket)
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <a href="/tickets/{{ $ticket->id }}"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $ticket->title }}
                                </a>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $ticket->user->name }} - {{ $ticket->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="ml-4 flex items-center space-x-2">
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if ($ticket->status === 'abierto') bg-green-100 text-green-800
                                    @elseif($ticket->status === 'en_proceso') bg-yellow-100 text-yellow-800
                                    @elseif($ticket->status === 'resuelto') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if ($ticket->priority === 'urgente') bg-red-100 text-red-800
                                    @elseif($ticket->priority === 'alta') bg-orange-100 text-orange-800
                                    @elseif($ticket->priority === 'media') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800 @endif
                                ">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-6 py-8 text-center text-gray-500">
                <p>No hay tickets recientes</p>
            </div>
        @endif
    </div> --}}

    <!-- Assign Tickets Section -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Asignar Tickets</h3>
            <p class="text-sm text-gray-600">Elige el ticket y uno o varios agentes.</p>
        </div>
        <div class="p-6">
            @if (session()->has('message'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                    {{ session('message') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ticket</label>
                    <select wire:model.live="selectedTicketId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar ticket</option>
                        @forelse ($assignableTickets as $ticket)
                            <option value="{{ $ticket->id }}">
                                #{{ $ticket->id }} - {{ Str::limit($ticket->title, 40) }}
                                ({{ ucfirst(str_replace('_', ' ', $ticket->status)) }})
                                @if ($ticket->assignedAgents->isNotEmpty())
                                    — {{ $ticket->assignedAgents->pluck('name')->join(', ') }}
                                @endif
                            </option>
                        @empty
                            <option value="" disabled>No hay tickets abiertos o en proceso</option>
                        @endforelse
                    </select>
                    @error('selectedTicketId')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-4">
                    @foreach ($pickedAgentSlots as $index => $slotValue)
                        @php
                            $excludeIds = [];
                            foreach ($pickedAgentSlots as $i => $v) {
                                if ($i !== $index && $v !== '' && $v !== null) {
                                    $excludeIds[] = (string) $v;
                                }
                            }
                            $isTrailingEmpty = $slotValue === '' && $index === array_key_last($pickedAgentSlots);
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Agente
                                {{ $index + 1 }}</label>
                            {{-- @if ($isTrailingEmpty && $index > 0)
                                Otro agente (opcional)
                            @else
                                Agente {{ $index + 1 }}
                            @endif --}}
                            </label>
                            <select wire:model.live="pickedAgentSlots.{{ $index }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">
                                    @if ($isTrailingEmpty && $index === 0)
                                        Seleccionar agente
                                    @elseif ($isTrailingEmpty)
                                        Elegir otro agente
                                    @else
                                        Sin selección
                                    @endif
                                </option>
                                @foreach ($agents as $agent)
                                    @php $idStr = (string) $agent->id; @endphp
                                    @if (in_array($idStr, $excludeIds, true) && $idStr !== (string) $slotValue)
                                        @continue
                                    @endif
                                    <option value="{{ $agent->id }}">
                                        {{ $agent->name }} {{ $agent->id === auth()->id() ? '(Tú)' : '' }}
                                        @if ($agent->isAdmin())
                                            (Admin)
                                        @elseif($agent->isAgent())
                                            (Agente)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    @error('pickedAgentSlots')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-3 flex items-end">
                    <button type="button" wire:click="assignTicket" wire:loading.attr="disabled"
                        class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="assignTicket">Añadir asignación</span>
                        <span wire:loading wire:target="assignTicket">Guardando…</span>
                    </button>
                </div>
            </div>

            <!-- All Tickets List -->
            <div class="border-t border-gray-200 pt-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Todos los Tickets</h4>
                <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:gap-2">
                    <div class="sm:max-w-xs">
                        <label for="min" class="block text-md font-medium text-gray-700">Fecha desde</label>
                        <input id="min" type="text" placeholder="Seleccionar fecha"
                            class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                    <div class="sm:max-w-xs">
                        <label for="max" class="block text-md font-medium text-gray-700">Fecha hasta</label>
                        <input id="max" type="text" placeholder="Seleccionar fecha"
                            class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                </div>
                @if ($allTickets->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="example">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ID</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Título</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Creador</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Agentes</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Prioridad</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($allTickets as $ticket)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #{{ $ticket->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <a href="/tickets/{{ $ticket->id }}"
                                                class="text-indigo-600 hover:text-indigo-900">
                                                {{ Str::limit($ticket->title, 30) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $ticket->user->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                            @if ($ticket->assignedAgents->isNotEmpty())
                                                {{ $ticket->assignedAgents->pluck('name')->join(', ') }}
                                            @else
                                                <span class="text-gray-400">Sin asignar</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $ticket->created_at->format('Y-m-d') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if ($ticket->status === 'abierto') bg-green-100 text-green-800
                                                @elseif($ticket->status === 'en_proceso') bg-yellow-100 text-yellow-800
                                                @elseif($ticket->status === 'resuelto') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif
                                            ">
                                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if ($ticket->priority === 'urgente') bg-red-100 text-red-800
                                                @elseif($ticket->priority === 'alta') bg-orange-100 text-orange-800
                                                @elseif($ticket->priority === 'media') bg-yellow-100 text-yellow-800
                                                @else bg-green-100 text-green-800 @endif
                                            ">
                                                {{ ucfirst($ticket->priority) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-gray-500 py-8">
                        <p>No hay tickets en el sistema</p>
                    </div>
                @endif
            </div>
        </div>
        <script>
            // DataTables initialisation
            let table = new DataTable('#example', {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.3.8/i18n/es-ES.json'
                }
            });

            const minInput = document.querySelector('#min');
            const maxInput = document.querySelector('#max');

            if (minInput && maxInput && typeof DateTime !== 'undefined') {
                let minDate = new DateTime('#min', {
                    format: 'YYYY-MM-DD'
                });
                let maxDate = new DateTime('#max', {
                    format: 'YYYY-MM-DD'
                });

                DataTable.ext.search.push(function(settings, data, dataIndex) {
                    let min = minDate.val();
                    let max = maxDate.val();
                    let date = new Date(data[4]);

                    if (
                        (min === null && max === null) ||
                        (min === null && date <= max) ||
                        (min <= date && max === null) ||
                        (min <= date && date <= max)
                    ) {
                        return true;
                    }
                    return false;
                });

                minInput.addEventListener('change', () => table.draw());
                maxInput.addEventListener('change', () => table.draw());
            }
        </script>
    </div>
</div>
