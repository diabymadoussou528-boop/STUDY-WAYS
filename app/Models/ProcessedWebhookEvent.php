<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedWebhookEvent extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public static function alreadyProcessed(string $provider, string $eventId): bool
    {
        return static::query()
            ->where('provider', $provider)
            ->where('event_id', $eventId)
            ->exists();
    }

    public static function record(string $provider, string $eventId, string $eventType): void
    {
        static::query()->firstOrCreate(
            ['provider' => $provider, 'event_id' => $eventId],
            ['event_type' => $eventType, 'processed_at' => now()],
        );
    }
}
