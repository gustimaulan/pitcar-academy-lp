<?php

namespace Tests\Feature\Lms;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentSource;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Mail\LoginLinkMail;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Student;
use App\Models\User;
use App\Services\Analytics\OnlineReport;
use App\Services\Lms\InternalAccess;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class InternalAccessTest extends TestCase
{
    use RefreshDatabase;

    private Course $ac;

    private Course $inspeksi;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
        Mail::fake();
        $this->ac = Course::create(['title' => 'Kelas AC', 'slug' => 'kelas-online-ac', 'price' => 99000, 'status' => CourseStatus::Published]);
        $this->inspeksi = Course::create(['title' => 'Kelas Inspeksi', 'slug' => 'kelas-online-inspeksi', 'price' => 149000, 'status' => CourseStatus::Published]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'name' => 'Teddi']);
    }

    public function test_an_admin_opens_classes_for_a_new_team_member_without_an_order(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(ListStudents::class)
            ->callAction('giveInternalAccess', [
                'email' => 'Rina@Pitcar.co.id',
                'name' => 'Rina',
                'whatsapp' => '0812-3456-7890',
                'courses' => [$this->ac->id, $this->inspeksi->id],
                'send_email' => true,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Akses gratis dibuka untuk rina@pitcar.co.id');

        $student = Student::where('email', 'rina@pitcar.co.id')->firstOrFail();
        $this->assertSame('6281234567890', $student->whatsapp);
        $this->assertTrue($student->hasAccessTo($this->ac));
        $this->assertTrue($student->hasAccessTo($this->inspeksi));
        $this->assertSame([EnrollmentSource::Internal], $student->enrollments->pluck('source')->unique()->values()->all());
        $this->assertSame([$admin->id], $student->enrollments->pluck('granted_by')->unique()->values()->all());

        // Free access is not a sale.
        $this->assertSame(0, Order::count());
        $this->assertSame(0, OnlineReport::forFilters(['range' => 'all'])->summary()['sales']);

        Mail::assertSent(LoginLinkMail::class, fn (LoginLinkMail $m) => $m->free
            && $m->hasTo('rina@pitcar.co.id')
            && $m->title === 'Kelas AC dan Kelas Inspeksi'
            && str_contains($m->render(), 'akses gratis'));
        Mail::assertSentCount(1);
    }

    public function test_an_existing_buyer_keeps_their_details_and_paid_access(): void
    {
        $student = Student::create(['name' => 'Budi Asli', 'email' => 'budi@example.test', 'whatsapp' => '628111']);
        $order = Order::create(['number' => 'PA0000000000PAID', 'student_id' => $student->id, 'course_id' => $this->ac->id, 'amount' => 99000, 'status' => OrderStatus::Paid, 'paid_at' => now()]);
        $student->enrollments()->create(['course_id' => $this->ac->id, 'order_id' => $order->id, 'source' => EnrollmentSource::Doku, 'granted_at' => now()]);

        $result = app(InternalAccess::class)->give('Budi Lain', 'BUDI@example.test', '0899', collect([$this->ac, $this->inspeksi]), $this->admin(), sendEmail: false);

        $this->assertSame(['Kelas Inspeksi'], $result['opened']->pluck('title')->all());
        $this->assertSame(['Kelas AC'], $result['had']->pluck('title')->all());
        $student->refresh();
        $this->assertSame('Budi Asli', $student->name);
        $this->assertSame('628111', $student->whatsapp);
        $this->assertSame(EnrollmentSource::Doku, $student->enrollments()->where('course_id', $this->ac->id)->value('source'));
        Mail::assertNothingSent();
    }

    public function test_the_sales_page_count_leaves_out_team_access(): void
    {
        $buyer = Student::create(['name' => 'Budi', 'email' => 'budi@example.test']);
        $buyer->enrollments()->create(['course_id' => $this->ac->id, 'source' => EnrollmentSource::Doku, 'granted_at' => now()]);
        app(InternalAccess::class)->give('Rina', 'rina@pitcar.co.id', null, collect([$this->ac]), $this->admin(), sendEmail: false);

        $this->getJson('/api/checkout/courses/kelas-online-ac')->assertJsonPath('course.students', 1);
    }

    public function test_only_free_team_access_can_be_revoked_here(): void
    {
        $this->actingAs($this->admin());
        app(InternalAccess::class)->give('Rina', 'rina@pitcar.co.id', null, collect([$this->ac]), auth()->user(), sendEmail: false);
        $internal = Enrollment::where('source', EnrollmentSource::Internal)->firstOrFail();
        $buyer = Student::create(['name' => 'Budi', 'email' => 'budi@example.test']);
        $paid = $buyer->enrollments()->create(['course_id' => $this->ac->id, 'source' => EnrollmentSource::Doku, 'granted_at' => now()]);

        Livewire::test(StudentsRelationManager::class, ['ownerRecord' => $this->ac, 'pageClass' => EditCourse::class])
            ->assertActionHidden(TestAction::make('revokeInternal')->table($paid))
            ->callAction(TestAction::make('revokeInternal')->table($internal))
            ->assertNotified('Akses rina@pitcar.co.id dicabut');

        $this->assertNotNull($internal->refresh()->revoked_at);
        $this->assertFalse($internal->student->hasAccessTo($this->ac));

        $this->expectException(InvalidArgumentException::class);
        app(InternalAccess::class)->revoke($paid);
    }

    public function test_access_given_again_after_revoking_reopens_the_class(): void
    {
        $admin = $this->admin();
        app(InternalAccess::class)->give('Rina', 'rina@pitcar.co.id', null, collect([$this->ac]), $admin, sendEmail: false);
        app(InternalAccess::class)->revoke(Enrollment::firstOrFail());

        $result = app(InternalAccess::class)->give('Rina', 'rina@pitcar.co.id', null, collect([$this->ac]), $admin, sendEmail: false);

        $this->assertCount(1, $result['opened']);
        $this->assertTrue($result['student']->hasAccessTo($this->ac));
        $this->assertSame(1, Enrollment::count());
    }

    public function test_managers_see_the_list_but_cannot_give_free_access(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Manager, 'is_active' => true]));

        Livewire::test(ListStudents::class)->assertActionHidden('giveInternalAccess');
        Livewire::test(StudentsRelationManager::class, ['ownerRecord' => $this->ac, 'pageClass' => EditCourse::class])
            ->assertActionHidden(TestAction::make('giveInternalAccess')->table());
    }

    public function test_the_class_tab_preselects_its_class(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(StudentsRelationManager::class, ['ownerRecord' => $this->inspeksi, 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make('giveInternalAccess')->table(), ['email' => 'rina@pitcar.co.id', 'name' => 'Rina', 'send_email' => false])
            ->assertHasNoFormErrors();

        $this->assertTrue(Student::where('email', 'rina@pitcar.co.id')->firstOrFail()->hasAccessTo($this->inspeksi));
        $this->assertFalse(Student::where('email', 'rina@pitcar.co.id')->firstOrFail()->hasAccessTo($this->ac));
    }
}
