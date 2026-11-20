<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestKind;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Student;
use App\Models\StudentRequest;
use App\Services\Lms\TeamAlerts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ratings and requests from students. Both are read by the team in the admin.
 * A review reaches the sales page only when the student allowed it and the
 * team chose it (CourseReview::published); requests are never shown.
 */
class LearnFeedbackController extends Controller
{
    public function saveReview(Request $request, string $slug): JsonResponse
    {
        $student = $this->student($request);
        $course = Course::where('slug', $slug)->firstOrFail();

        if (! $student->hasAccessTo($course)) {
            return response()->json(['message' => 'Kamu belum punya akses ke kelas ini.'], 403);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['nullable', 'string', 'max:2000'],
            'may_publish' => ['boolean'],
        ], [
            'rating.required' => 'Pilih nilai dari 1 sampai 5.',
            'rating.between' => 'Pilih nilai dari 1 sampai 5.',
            'body.max' => 'Ulasan maksimal 2.000 karakter.',
        ]);

        $review = CourseReview::firstOrNew(['student_id' => $student->id, 'course_id' => $course->id]);
        $review->fill([
            'rating' => $data['rating'],
            'body' => self::clean($data['body'] ?? null),
            'may_publish' => (bool) ($data['may_publish'] ?? false),
        ]);

        // The team chose the old words. New words, a new score, or a withdrawn
        // consent take the review off the sales page until the team looks again.
        if ($review->isDirty(['rating', 'body', 'may_publish'])) {
            $review->published_at = null;
        }

        $review->save();

        return response()->json(['review' => self::review($review)]);
    }

    public function requests(Request $request): JsonResponse
    {
        $rows = $this->student($request)->requests()->with('course:id,slug,title')->latest('id')->limit(50)->get();

        return response()->json(['requests' => $rows->map(fn (StudentRequest $row) => self::request($row))->values()]);
    }

    public function storeRequest(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $data = $request->validate([
            'kind' => ['required', Rule::enum(RequestKind::class)],
            'course' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'kind.required' => 'Pilih jenis pesan.',
            'kind.enum' => 'Pilih jenis pesan.',
            'body.required' => 'Tulis pesanmu dulu.',
            'body.min' => 'Pesan minimal 10 karakter, supaya tim kami paham maksudnya.',
            'body.max' => 'Pesan maksimal 2.000 karakter.',
        ]);

        $courseId = null;

        if (! empty($data['course'])) {
            $course = Course::where('slug', $data['course'])->first();

            if ($course === null || ! $student->hasAccessTo($course)) {
                return response()->json(['message' => 'Kelas yang dipilih tidak ada di akunmu.', 'errors' => ['course' => ['Kelas yang dipilih tidak ada di akunmu.']]], 422);
            }

            $courseId = $course->id;
        }

        $row = $student->requests()->create([
            'kind' => $data['kind'],
            'course_id' => $courseId,
            'body' => self::clean($data['body']),
            'status' => RequestStatus::New,
        ]);

        $row->load('course:id,slug,title');
        app(TeamAlerts::class)->newRequest($row);

        return response()->json(['request' => self::request($row)], 201);
    }

    public static function review(?CourseReview $review): ?array
    {
        return $review === null ? null : [
            'rating' => $review->rating,
            'body' => $review->body,
            'may_publish' => $review->may_publish,
            'updated_at' => $review->updated_at?->toIso8601String(),
        ];
    }

    private static function request(StudentRequest $row): array
    {
        return [
            'id' => $row->id,
            'kind' => $row->kind->value,
            'course' => $row->course ? ['slug' => $row->course->slug, 'title' => $row->course->title] : null,
            'body' => $row->body,
            'status' => $row->status->value,
            'reply' => $row->reply,
            'replied_at' => $row->replied_at?->toIso8601String(),
            'created_at' => $row->created_at?->toIso8601String(),
        ];
    }

    private static function clean(?string $text): ?string
    {
        $text = trim((string) $text);

        return $text === '' ? null : $text;
    }

    private function student(Request $request): Student
    {
        return $request->attributes->get('student');
    }
}
