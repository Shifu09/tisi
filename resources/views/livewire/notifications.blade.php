<?php

use Livewire\Volt\Component;
use Illuminate\Notifications\DatabaseNotification;

new class extends Component {
    public $notifications;
    public $unreadCount;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $this->notifications = auth()->user()->notifications()->latest()->limit(10)->get();
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead($notificationId)
    {
        $notification = DatabaseNotification::find($notificationId);
        
        if ($notification && $notification->notifiable_id === auth()->id()) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->loadNotifications();
    }

    public function with()
    {
        return [
            'notifications' => $this->notifications,
            'unreadCount' => $this->unreadCount,
        ];
    }
}; ?>

<div>
    <div class="relative">
        <button
            wire:click="$toggle('showDropdown')"
            class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($unreadCount > 0)
                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <div wire:loading.delay.longer class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="p-4 text-center">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
            </div>
        </div>

        @if($showDropdown)
            <div class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Notificaciones</h3>
                        @if($unreadCount > 0)
                            <button
                                wire:click="markAllAsRead"
                                class="text-sm text-indigo-600 hover:text-indigo-900"
                            >
                                Marcar todas como leídas
                            </button>
                        @endif
                    </div>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    @if($notifications->count() === 0)
                        <div class="p-8 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <p class="mt-2">No tienes notificaciones</p>
                        </div>
                    @else
                        @foreach($notifications as $notification)
                            <div class="p-4 border-b border-gray-200 hover:bg-gray-50 {{ $notification->read_at ? 'opacity-60' : '' }}"
                                 wire:click="markAsRead('{{ $notification->id }}')">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        @if($notification->data['type'] === 'ticket_created')
                                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </div>
                                        @elseif($notification->data['type'] === 'ticket_assigned')
                                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @elseif($notification->data['type'] === 'ticket_updated')
                                            <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $notification->data['title'] }}
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ $notification->data['message'] }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    @if(!$notification->read_at)
                                        <div class="ml-2">
                                            <div class="h-2 w-2 rounded-full bg-indigo-600"></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="p-4 border-t border-gray-200">
                    <a href="/notifications" class="block text-center text-sm text-indigo-600 hover:text-indigo-900">
                        Ver todas las notificaciones
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
