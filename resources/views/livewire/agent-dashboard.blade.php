<?php

use Livewire\Volt\Component;
use App\Models\Ticket;

new class extends Component {
    public $assignedTickets;
    public $unassignedTickets;

    public function mount()
    {
        $this->loadTickets();
    }

    public function loadTickets()
    {
        $this->assignedTickets = auth()
            ->user()
            ->assignedTickets()
            ->with(['user', 'category'])
            ->latest()
            ->get();

        $this->unassignedTickets = Ticket::whereNull('assigned_to')
            ->where('status', '!=', 'cerrado')
            ->with(['user', 'category'])
            ->latest()
            ->get();
    }

    public function assignToMe($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->assigned_to === null) {
            $ticket->update([
                'assigned_to' => auth()->id(),
                'status' => 'en_proceso',
            ]);

            // Notificar al usuario creador
            $ticket->user->notify(new \App\Notifications\TicketAssigned($ticket));

            $this->loadTickets();
        }
    }

    public function updateStatus($ticketId, $status)
    {
        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->assigned_to === auth()->id()) {
            $ticket->update([
                'status' => $status,
                'resolved_at' => $status === 'resuelto' ? now() : null,
            ]);

            // Notificar al usuario creador
            $ticket->user->notify(new \App\Notifications\TicketUpdated($ticket, "cambiado a estado: {$status}"));

            $this->loadTickets();
        }
    }

    public function with()
    {
        return [
            'assignedTickets' => $this->assignedTickets,
            'unassignedTickets' => $this->unassignedTickets,
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Dashboard de Agente</h2>
        <p class="mt-1 text-sm text-gray-600">Gestiona los tickets asignados y disponibles</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Tickets Asignados -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Mis Tickets Asignados ({{ $assignedTickets->count() }})
                </h3>
            </div>
            @if ($assignedTickets->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach ($assignedTickets as $ticket)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <a href="/tickets/{{ $ticket->id }}"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $ticket->title }}
                                </a>
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if ($ticket->status === 'abierto') bg-green-100 text-green-800
                                    @elseif($ticket->status === 'en_proceso') bg-yellow-100 text-yellow-800
                                    @elseif($ticket->status === 'resuelto') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ Str::limit($ticket->description, 80) }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $ticket->created_at->diffForHumans() }}</span>
                                <div class="flex space-x-2">
                                    <button wire:click="updateStatus({{ $ticket->id }}, 'en_proceso')"
                                        class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200">
                                        En Proceso
                                    </button>
                                    <button wire:click="updateStatus({{ $ticket->id }}, 'resuelto')"
                                        class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded hover:bg-green-200">
                                        Resuelto
                                    </button>
                                    <button wire:click="updateStatus({{ $ticket->id }}, 'cerrado')"
                                        class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded hover:bg-gray-200">
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>No tienes tickets asignados</p>
                </div>
            @endif
        </div>

        <!-- Tickets Disponibles -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Tickets Disponibles ({{ $unassignedTickets->count() }})
                </h3>
            </div>
            @if ($unassignedTickets->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach ($unassignedTickets as $ticket)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <a href="/tickets/{{ $ticket->id }}"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $ticket->title }}
                                </a>
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
                            <p class="text-sm text-gray-600 mb-2">{{ Str::limit($ticket->description, 80) }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $ticket->user->name }} -
                                    {{ $ticket->created_at->diffForHumans() }}</span>
                                <button wire:click="assignToMe({{ $ticket->id }})"
                                    class="px-3 py-1 text-xs bg-indigo-100 text-indigo-800 rounded hover:bg-indigo-200">
                                    Asignar a mí
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>No hay tickets disponibles para asignar</p>
                </div>
            @endif
        </div>
    </div>
</div>
