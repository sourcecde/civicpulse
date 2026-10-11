<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Report extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_id',
        'description',
        'location',
        'tracking_number',
        'reporter_email',
        'reporter_id',
        'guest_token_hash',
        'status',
        'assigned_operator_id',
        'assigned_at',
        'started_at',
        'resolved_at',
        'version'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class);
    }

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'version' => 'integer',
        ];
    }

}
