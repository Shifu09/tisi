<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use App\Models\Category;
use App\Models\Ticket;
use App\Notifications\TicketCreated;

new class extends Component {
    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|string')]
    public $description = '';

    #[Validate('required|in:baja,media,alta,urgente')]
    public $priority = 'media';

    #[Validate('nullable|exists:categories,id')]
    public $category_id = '';

    public function createTicket()
    {
        $this->validate();

        $ticket = Ticket::create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'category_id' => $this->category_id ?: null,
            'user_id' => auth()->id(),
        ]);

        // Notificar a todos los agentes sobre el nuevo ticket
        $agents = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('slug', ['agent', 'admin']);
        })->get();

        foreach ($agents as $agent) {
            $agent->notify(new TicketCreated($ticket));
        }

        $this->reset(['title', 'description', 'priority', 'category_id']);
        
        session()->flash('message', 'Ticket creado exitosamente');
        
        $this->redirect('/tickets');
    }

    public function with()
    {
        return [
            'categories' => Category::all(),
        ];
    }
}; ?>

<div>
    <form wire:submit="createTicket" class="space-y-6">
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Título</label>
            <input
                type="text"
                id="title"
                wire:model="title"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            />
            @error('title')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
            <textarea
                id="description"
                wire:model="description"
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            ></textarea>
            @error('description')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="priority" class="block text-sm font-medium text-gray-700">Prioridad</label>
            <select
                id="priority"
                wire:model="priority"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
            </select>
            @error('priority')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700">Categoría</label>
            <select
                id="category_id"
                wire:model="category_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">Seleccionar categoría</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <button
                type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Crear Ticket
            </button>
        </div>

        @if(session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('message') }}
            </div>
        @endif
    </form>
</div>
