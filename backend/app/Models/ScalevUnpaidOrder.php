<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScalevUnpaidOrder extends Model
{
    /** Older than this, an unpaid Scalev order is dropped from the follow-up list. */
    public const KEEP_DAYS = 7;

    protected $primaryKey = 'order_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['order_id', 'customer_name', 'email', 'whatsapp', 'product_name', 'course_id', 'amount', 'ordered_at', 'followed_up_at', 'followed_up_by'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'ordered_at' => 'datetime', 'followed_up_at' => 'datetime'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function followedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followed_up_by');
    }

    /**
     * Recent, and the buyer does not have the class yet: someone who since
     * bought it on the new checkout needs no message.
     */
    public function scopeToFollowUp(Builder $query): Builder
    {
        return $query
            ->where('ordered_at', '>=', now()->subDays(self::KEEP_DAYS))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('enrollments')
                ->join('students', 'students.id', '=', 'enrollments.student_id')
                ->whereColumn('students.email', 'scalev_unpaid_orders.email')
                ->whereColumn('enrollments.course_id', 'scalev_unpaid_orders.course_id')
                ->whereNull('enrollments.revoked_at'));
    }
}
