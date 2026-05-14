<?php

use Livewire\Volt\Component;
use App\Models\Ticket;
use App\Models\Category;

new class extends Component {
    public $totalTickets;
    public $openTickets;
    public $inProgressTickets;
    public $resolvedTickets;
    public $ticketsByCategory;
    public $ticketsByPriority;
    public $recentTickets;

    public function mount()
    {
        $this->loadStatistics();
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
    <div class="bg-white rounded-lg shadow">
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
    </div>
</div>
