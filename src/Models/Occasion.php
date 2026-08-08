<?php

namespace Platform\Arbmedvv\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\UuidV7;

/**
 * An occupational-health preventive-care occasion from the annex of the ArbMedVV.
 *
 * section:   annex part 1–4 (hazardous_substances|biological_agents|physical_agents|other)
 * care_type: mandatory|offered|follow_up
 */
class Occasion extends Model
{
    use SoftDeletes;

    protected $table = 'arbmedvv_occasions';

    protected $fillable = [
        'uuid',
        'team_id',
        'section',
        'care_type',
        'combination_group',
        'title',
        'trigger',
        'threshold',
        'legal_basis',
        'description',
        'status',
        'version',
        'valid_from',
        'valid_until',
        'regulation_label',
        'position',
        'created_by_user_id',
    ];

    protected $casts = [
        'position'    => 'integer',
        'version'     => 'integer',
        'valid_from'  => 'date',
        'valid_until' => 'date',
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
        return $query->where('status', 'active');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeByCareType($query, string $careType)
    {
        return $query->where('care_type', $careType);
    }

    // Labels (from config; display labels stay German)

    public function sectionLabel(): string
    {
        if (empty($this->section)) {
            return 'Anhangsunabhängig (§5a)';
        }

        return config("arbmedvv.sections.{$this->section}", $this->section);
    }

    public function sectionShortLabel(): string
    {
        if (empty($this->section)) {
            return 'Anhangsunabhängig';
        }

        return config("arbmedvv.sections_short.{$this->section}", $this->sectionLabel());
    }

    /** Gültig zum Stichtag (Versionierung / Novellierungen). */
    public function isCurrentlyValid($asOf = null): bool
    {
        $asOf = $asOf ? \Illuminate\Support\Carbon::parse($asOf) : \Illuminate\Support\Carbon::now();

        if ($this->valid_from && $this->valid_from->gt($asOf)) {
            return false;
        }
        if ($this->valid_until && $this->valid_until->lt($asOf)) {
            return false;
        }

        return true;
    }

    public function careTypeLabel(): string
    {
        return config("arbmedvv.care_types.{$this->care_type}", $this->care_type);
    }
}
