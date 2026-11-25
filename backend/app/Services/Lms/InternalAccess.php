<?php

namespace App\Services\Lms;

use App\Enums\EnrollmentSource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use App\Support\WhatsAppNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Free class access for the team. No order is made, so revenue, conversion and
 * Meta purchase events stay about real buyers. The account is the usual
 * student account, found or made by email.
 */
class InternalAccess
{
    public function __construct(private EnrollmentGrant $grant, private LoginLink $links) {}

    /**
     * @param  Collection<int, Course>  $courses
     * @return array{student: Student, opened: Collection<int, Course>, had: Collection<int, Course>}
     */
    public function give(string $name, string $email, ?string $whatsapp, Collection $courses, User $by, bool $sendEmail = true): array
    {
        if ($courses->isEmpty()) {
            throw new InvalidArgumentException('Pilih minimal satu kelas.');
        }

        [$student, $opened, $had] = DB::transaction(function () use ($name, $email, $whatsapp, $courses, $by) {
            $student = Student::firstOrNew(['email' => mb_strtolower(trim($email))]);
            // An existing account keeps its name and number unless they were empty.
            $student->name = $student->exists && filled($student->name) ? $student->name : trim($name);
            $student->whatsapp = $student->whatsapp ?: WhatsAppNumber::normalize($whatsapp);
            $student->save();

            [$had, $opened] = $courses->partition(fn (Course $c) => $student->hasAccessTo($c));
            $opened->each(fn (Course $c) => $this->grant->grant($student, $c, EnrollmentSource::Internal, by: $by));

            return [$student, $opened->values(), $had->values()];
        });

        if ($sendEmail && $opened->isNotEmpty()) {
            $this->links->send($student, $opened->first(), $opened->pluck('title')->join(', ', ' dan '), free: true);
        }

        return ['student' => $student, 'opened' => $opened, 'had' => $had];
    }

    /** Only free team access can be taken back here; paid access goes through refunds. */
    public function revoke(Enrollment $enrollment): void
    {
        if ($enrollment->source !== EnrollmentSource::Internal) {
            throw new InvalidArgumentException('Hanya akses tim internal yang bisa dicabut dari sini.');
        }

        $this->grant->revoke($enrollment);
    }
}
