<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class CourseReview extends Model
{
    protected $fillable = ['student_id', 'course_id', 'rating', 'body', 'may_publish', 'published_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'may_publish' => 'boolean', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // The sales page reads a cached copy; a choice in the admin shows at once.
        $forget = fn (CourseReview $review) => Cache::forget('course-reviews:'.$review->course_id);
        static::saved($forget);
        static::deleted($forget);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** The team may show it: the student allowed it and wrote something. */
    public function canBePublished(): bool
    {
        return $this->may_publish && filled($this->body);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->canBePublished();
    }

    /** On the sales page: allowed by the student and chosen by the team. */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('may_publish', true)->whereNotNull('body');
    }

    /** "Muhammad Jafar" becomes "Muhammad": the student agreed to their first name only. */
    public function publicName(): string
    {
        return str((string) $this->student?->name)->squish()->before(' ')->title()->limit(24, '')->value() ?: 'Siswa';
    }
}
