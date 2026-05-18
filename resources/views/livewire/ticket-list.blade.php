<?php

use Livewire\Volt\Component;
use App\Models\Ticket;

new class extends Component {
    public function with()
    {
        return [
            'tickets' => auth()->user()->tickets()->latest()->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Mis Tickets</h2>
        <p class="mt-1 text-sm text-gray-600">Lista de todos tus tickets de soporte</p>
    </div>

    @if ($tickets->count() === 0)
        <div class="text-center py-12">
            <div class="text-gray-400 text-6xl mb-4">📋</div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No tienes tickets</h3>
            <p class="text-gray-600 mb-6">Crea tu primer ticket de soporte</p>
            <a href="/tickets/create"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Crear Ticket
            </a>
        </div>
    @else
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @foreach ($tickets as $ticket)
                    <li>
                        <a href="/tickets/{{ $ticket->id }}" class="block hover:bg-gray-50">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            {{ $ticket->title }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ Str::limit($ticket->description, 100) }}
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
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
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                        <span>
                                            Prioridad:
                                            <span
                                                class="font-medium
                                                @if ($ticket->priority === 'urgente') text-red-600
                                                @elseif($ticket->priority === 'alta') text-orange-600
                                                @elseif($ticket->priority === 'media') text-yellow-600
                                                @else text-green-600 @endif
                                            ">
                                                {{ ucfirst($ticket->priority) }}
                                            </span>
                                        </span>
                                        @if ($ticket->category)
                                            <span>Categoría: {{ $ticket->category->name }}</span>
                                        @endif
                                        <span>Creado: {{ $ticket->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6">
            <a href="/tickets/create"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Crear Nuevo Ticket
            </a>
        </div>
    @endif
</div>
