@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Crear Nuevo Ticket</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Describe tu problema o solicitud de soporte técnico.
                </p>
            </div>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="shadow sm:rounded-md sm:overflow-hidden">
                <div class="px-4 py-5 bg-white sm:p-6">
                    <livewire:ticket-create />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
