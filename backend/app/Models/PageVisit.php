<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    use HasUuids;

    public $timestamps = false;

    /** A tab left open is still counted, but never for more than this. */
    public const MAX_SECONDS = 1800;

    protected $fillable = ['id', 'visitor_id', 'visit_date', 'path', 'source', 'referrer_host', 'utm_campaign', 'device', 'duration_seconds', 'started_at'];

    protected function casts(): array
    {
        // visit_date stays a Y-m-d string: a date cast would store a time too and break day ranges in SQLite.
        return ['started_at' => 'datetime', 'duration_seconds' => 'integer'];
    }
}
