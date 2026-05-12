<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class TicketCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'ticket_created',
            'title' => 'Nuevo ticket creado',
            'message' => "Se ha creado el ticket #{$this->ticket->id}: {$this->ticket->title}",
            'ticket_id' => $this->ticket->id,
            'priority' => $this->ticket->priority,
        ];
    }
}
