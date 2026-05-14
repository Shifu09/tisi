<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use App\Models\Ticket;

new class extends Component {
    public $ticket;

    public function mount($id)
    {
        $this->ticket = Ticket::with(['user', 'assignedTo', 'category'])->findOrFail($id);

        if ($this->ticket->user_id !== auth()->id() && !auth()->user()->isAgent()) {
            abort(403);
        }
    }

    public function with()
    {
        return [
            'ticket' => $this->ticket,
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <a href="/tickets" class="text-indigo-600 hover:text-indigo-900 mb-4 inline-block">
            ← Volver a mis tickets
        </a>
        <h2 class="text-2xl font-bold text-gray-900">{{ $ticket->title }}</h2>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
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
                        Prioridad: {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
                <div class="text-sm text-gray-500">
                    #{{ $ticket->id }}
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Descripción</h3>
                    <p class="mt-2 text-gray-600">{{ $ticket->description }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Creado por</h4>
                        <p class="mt-1 text-gray-900">{{ $ticket->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    @if ($ticket->category)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Categoría</h4>
                            <p class="mt-1 text-gray-900">{{ $ticket->category->name }}</p>
                        </div>
                    @endif

                    @if ($ticket->assignedTo)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Asignado a</h4>
                            <p class="mt-1 text-gray-900">{{ $ticket->assignedTo->name }}</p>
                        </div>
                    @endif

                    @if ($ticket->resolved_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Resuelto el</h4>
                            <p class="mt-1 text-gray-900">{{ $ticket->resolved_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->isAgent())
        <div class="mt-6 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Acciones de Agente</h3>
                <div class="flex space-x-4">
                    <button class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                        Asignar a mí
                    </button>
                    <button class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Marcar como resuelto
                    </button>
                    <button class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        Cerrar ticket
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
