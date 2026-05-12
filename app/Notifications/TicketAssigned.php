<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class TicketAssigned extends Notification implements ShouldQueue
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
            'type' => 'ticket_assigned',
            'title' => 'Ticket asignado',
            'message' => "El ticket #{$this->ticket->id} ha sido asignado a ti",
            'ticket_id' => $this->ticket->id,
            'priority' => $this->ticket->priority,
        ];
    }
}
