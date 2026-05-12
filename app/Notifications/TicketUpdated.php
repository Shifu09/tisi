<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class TicketUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $ticket;
    public $change;

    public function __construct($ticket, $change)
    {
        $this->ticket = $ticket;
        $this->change = $change;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'ticket_updated',
            'title' => 'Ticket actualizado',
            'message' => "El ticket #{$this->ticket->id} ha sido {$this->change}",
            'ticket_id' => $this->ticket->id,
            'priority' => $this->ticket->priority,
        ];
    }
}
