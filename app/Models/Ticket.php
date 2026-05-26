<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ticket extends Model
{
    public const STATUSES_ASSIGNABLE = ['abierto', 'en_proceso'];
    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'category_id',
        'user_id',
        'assigned_to',
        'resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Agentes (usuarios con rol agente/admin) asignados al ticket.
     */
    public function assignedAgents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_agent')
            ->withTimestamps();
    }

    /**
     * IDs de usuario en la pivot (evita ambigüedad SQL users.id vs ticket_agent.id).
     *
     * @return list<int>
     */
    public function assignedAgentUserIds(): array
    {
        $relation = $this->assignedAgents();

        return $relation
            ->pluck($relation->getRelated()->qualifyColumn('id'))
            ->all();
    }

    /**
     * Mantiene assigned_to alineado con el primer registro del pivot (compatibilidad).
     */
    public function refreshPrimaryAssigneeFromPivot(): void
    {
        $firstId = $this->assignedAgents()
            ->orderBy('ticket_agent.id')
            ->first()?->id;

        if ($firstId !== $this->assigned_to) {
            $this->forceFill(['assigned_to' => $firstId])->saveQuietly();
        }
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUSES_ASSIGNABLE);
    }

    public function isAssignable(): bool
    {
        return in_array($this->status, self::STATUSES_ASSIGNABLE, true);
    }
}
