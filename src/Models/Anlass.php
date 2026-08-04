<?php

namespace Platform\Arbmedvv\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\UuidV7;

/**
 * Ein arbeitsmedizinischer Vorsorge-Anlass nach dem Anhang der ArbMedVV.
 *
 * teil:        Teil 1–4 (gefahrstoffe|biostoffe|physikalisch|sonstige)
 * vorsorgeart: pflicht|angebot|nachgehend
 */
class Anlass extends Model
{
    use SoftDeletes;

    protected $table = 'arbmedvv_anlaesse';

    protected $fillable = [
        'uuid',
        'team_id',
        'teil',
        'vorsorgeart',
        'titel',
        'ausloeser',
        'grenzwert',
        'rechtsgrundlage',
        'beschreibung',
        'status',
        'position',
        'created_by_user_id',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
        });
    }

    // Relationships

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    // Scopes

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktiv');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTeil($query, string $teil)
    {
        return $query->where('teil', $teil);
    }

    public function scopeByVorsorgeart($query, string $vorsorgeart)
    {
        return $query->where('vorsorgeart', $vorsorgeart);
    }

    // Labels (aus config)

    public function teilLabel(): string
    {
        return config("arbmedvv.teile.{$this->teil}", $this->teil);
    }

    public function teilKurzLabel(): string
    {
        return config("arbmedvv.teile_kurz.{$this->teil}", $this->teilLabel());
    }

    public function vorsorgeartLabel(): string
    {
        return config("arbmedvv.vorsorgearten.{$this->vorsorgeart}", $this->vorsorgeart);
    }
}
