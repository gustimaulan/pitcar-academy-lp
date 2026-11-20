<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Support\TrafficSource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Throwable;

/**
 * Page views from the public site. Answers 204 whatever happens: a visitor's
 * page must never wait on, or show, an analytics problem.
 */
class VisitController extends Controller
{
    private const BOTS = '/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|preview|facebookexternalhit|whatsapp|curl|wget|python|axios|httpclient/i';

    public function start(Request $request): Response
    {
        try {
            $data = $request->json()->all();
            $id = (string) ($data['id'] ?? '');
            $visitor = (string) ($data['visitor'] ?? '');
            $path = '/'.ltrim((string) parse_url((string) ($data['path'] ?? ''), PHP_URL_PATH), '/');

            if (! Str::isUuid($id) || ! Str::isUuid($visitor) || preg_match(self::BOTS, (string) $request->userAgent())) {
                return response()->noContent();
            }

            $entry = (array) ($data['entry'] ?? []);
            $ownHost = TrafficSource::host(config('lms.frontend_url'));

            PageVisit::firstOrCreate(['id' => $id], [
                'visitor_id' => $visitor,
                'visit_date' => now(config('app.display_timezone'))->toDateString(),
                'path' => substr($path, 0, 191),
                'source' => TrafficSource::classify($entry, $ownHost),
                'referrer_host' => TrafficSource::host($entry['referrer'] ?? null),
                'utm_campaign' => filled($entry['utm_campaign'] ?? null) ? substr((string) $entry['utm_campaign'], 0, 191) : null,
                'device' => preg_match('/Mobi|Android|iPhone|iPad/i', (string) $request->userAgent()) ? 'mobile' : 'desktop',
                'started_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->noContent();
    }

    /** Sent each time the page is hidden; the latest total wins, never less than before. */
    public function time(Request $request, string $id): Response
    {
        try {
            $seconds = (int) ($request->json('seconds') ?? 0);

            if (Str::isUuid($id) && $seconds > 0) {
                PageVisit::whereKey($id)
                    ->where('duration_seconds', '<', min($seconds, PageVisit::MAX_SECONDS))
                    ->update(['duration_seconds' => min($seconds, PageVisit::MAX_SECONDS)]);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return response()->noContent();
    }
}
