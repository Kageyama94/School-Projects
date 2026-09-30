<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un billet n'est jamais effacé : annulé par le spectateur (cancelled_at), remboursé si l'épreuve est annulée,
 * ou sans propriétaire si le compte a été supprimé. L'historique des ventes reste ainsi juste.
 */
#[Fillable(['user_id', 'event_id', 'quantity', 'unit_price'])]
class Ticket extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Billets non annulés par leur détenteur (ceux qui occupent une place). */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('tickets.cancelled_at');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /** Remboursé : annulé par le spectateur, ou épreuve annulée par l'organisation. */
    public function isRefunded(): bool
    {
        return $this->isCancelled() || $this->event->isCancelled();
    }

    /** Montant payé : le prix est figé à la réservation, un changement de tarif ne le modifie pas. */
    public function total(): int
    {
        return $this->quantity * $this->unit_price;
    }
}
