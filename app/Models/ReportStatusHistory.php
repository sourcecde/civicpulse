<?php

    namespace App\Models;

    use App\Enums\ReportStatus;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class ReportStatusHistory extends Model
    {
        public $timestamps = false;

        protected $fillable = [
            'report_id',
            'actor_id',
            'from_status',
            'to_status',
            'from_operator_id',
            'to_operator_id',
            'reason',
        ];

        protected function casts(): array
        {
            return [
                'from_status' => ReportStatus::class,
                'to_status' => ReportStatus::class,
                'created_at' => 'datetime',
            ];
        }

        public function report(): BelongsTo
        {
            return $this->belongsTo(Report::class);
        }

        public function actor(): BelongsTo
        {
            return $this->belongsTo(User::class, 'actor_id');
        }

        public function fromOperator(): BelongsTo
        {
            return $this->belongsTo(User::class, 'from_operator_id');
        }

        public function toOperator(): BelongsTo
        {
            return $this->belongsTo(User::class, 'to_operator_id');
        }
    }
