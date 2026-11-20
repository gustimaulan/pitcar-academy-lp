<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Enums\EnrollmentSource;
use App\Filament\Resources\ScalevUnpaidOrders\Tables\ScalevUnpaidOrdersTable;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ScalevUnpaidOrder;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ScalevImportTest extends TestCase
{
    use RefreshDatabase;

    private Course $ac;

    private Course $inspeksi;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.scalev.api_key' => 'test-key']);
        $this->ac = Course::create(['title' => 'Kelas AC Mobil', 'slug' => 'kelas-online-ac', 'price' => 149000, 'status' => CourseStatus::Published]);
        $this->inspeksi = Course::create(['title' => 'Kelas Inspeksi Mobil Bekas', 'slug' => 'kelas-online-inspeksi', 'price' => 99000, 'status' => CourseStatus::Published]);
    }

    private function order(string $id, array $over = []): array
    {
        return array_replace([
            'order_id' => $id,
            'created_at' => now()->subDay()->toIso8601String(),
            'confirmed_time' => now()->subDay()->toIso8601String(),
            'completed_time' => null,
            'payment_status' => 'paid',
            'status' => 'completed',
            'gross_revenue' => '99000.00',
            'final_variants' => ['Kelas Inspeksi Mobil Bekas' => 1],
            'customer' => ['name' => 'Budi Santoso', 'email' => 'Budi@Example.test', 'phone' => '0812-3456-7890'],
            'destination_address' => ['city' => 'Surabaya'],
            'is_probably_spam' => false,
        ], $over);
    }

    /**
     * Each argument is one command run, as a list of pages. Two fakes on one
     * URL merge and the first wins, so every run shares one sequence.
     */
    private function fake(array ...$runs): void
    {
        $sequence = Http::sequence();

        foreach ($runs as $pages) {
            foreach ($pages as $i => $orders) {
                $last = $i === count($pages) - 1;
                $sequence->push(['data' => $orders, 'has_next' => ! $last, 'next_cursor' => $last ? null : 'c'.$i, 'page_size' => 25]);
            }
        }

        Http::fake(['api.scalev.com/v3/orders*' => $sequence]);
    }

    public function test_paid_buyers_get_their_class_quietly_across_every_page(): void
    {
        Mail::fake();
        $this->fake([
            [$this->order('A1')],
            [$this->order('A2', ['final_variants' => ['Bundling AC Mobil + Inspeksi' => 1], 'customer' => ['name' => 'Sari', 'email' => 'sari@example.test', 'phone' => '6281111111111']])],
        ]);

        $this->artisan('scalev:import')->assertSuccessful();

        $budi = Student::where('email', 'budi@example.test')->firstOrFail();
        $this->assertSame('6281234567890', $budi->whatsapp);
        $this->assertTrue($budi->hasAccessTo($this->inspeksi));
        $this->assertFalse($budi->hasAccessTo($this->ac));
        $this->assertSame(EnrollmentSource::Scalev, $budi->enrollments()->first()->source);

        // A bundle names both classes and opens both.
        $sari = Student::where('email', 'sari@example.test')->firstOrFail();
        $this->assertTrue($sari->hasAccessTo($this->ac));
        $this->assertTrue($sari->hasAccessTo($this->inspeksi));

        Mail::assertNothingOutgoing();
    }

    public function test_dry_run_writes_nothing_and_running_twice_creates_nothing_twice(): void
    {
        $this->fake([[$this->order('A1')]], [[$this->order('A1')]], [[$this->order('A1')]]);
        $this->artisan('scalev:import --dry-run')->assertSuccessful();
        $this->assertSame(0, Student::count());

        $this->artisan('scalev:import')->assertSuccessful();
        $this->artisan('scalev:import')->assertSuccessful();
        $this->assertSame(1, Student::count());
        $this->assertSame(1, Enrollment::count());
    }

    public function test_existing_students_keep_their_account_and_revoked_access_stays_revoked(): void
    {
        $student = Student::create(['name' => 'Budi', 'email' => 'budi@example.test']);
        Enrollment::create(['student_id' => $student->id, 'course_id' => $this->inspeksi->id, 'source' => EnrollmentSource::Manual, 'granted_at' => now(), 'revoked_at' => now()]);

        $this->fake([[$this->order('A1')]]);
        $this->artisan('scalev:import')->assertSuccessful();

        $this->assertSame(1, Student::count());
        $this->assertFalse($student->fresh()->hasAccessTo($this->inspeksi));
    }

    public function test_orders_without_email_or_a_matching_class_are_reported_not_imported(): void
    {
        $this->fake([[
            $this->order('A1', ['customer' => ['name' => 'Tanpa Email', 'phone' => '0812']]),
            $this->order('A2', ['final_variants' => ['Ebook Servis Rem' => 1], 'customer' => ['name' => 'X', 'email' => 'x@example.test']]),
        ]]);

        $this->artisan('scalev:import')
            ->expectsOutputToContain('Ebook Servis Rem')
            ->assertSuccessful();

        $this->assertSame(0, Enrollment::count());
    }

    public function test_recent_unpaid_orders_go_on_the_follow_up_list_and_leave_it_when_paid(): void
    {
        $this->fake([[
            $this->order('U1', ['status' => 'pending', 'payment_status' => 'unpaid', 'gross_revenue' => '79000']),
            $this->order('U2', ['status' => 'pending', 'payment_status' => 'unpaid', 'created_at' => now()->subDays(9)->toIso8601String()]),
            $this->order('U3', ['status' => 'canceled', 'payment_status' => 'unpaid']),
        ]], [[$this->order('U1')]]);
        $this->artisan('scalev:sync-purchases')->assertSuccessful();

        $row = ScalevUnpaidOrder::findOrFail('U1');
        $this->assertSame($this->inspeksi->id, $row->course_id);
        $this->assertSame(79000, $row->amount);
        $this->assertSame(['U1'], ScalevUnpaidOrder::pluck('order_id')->all());
        $this->assertSame(0, Enrollment::count());

        // Paid on Scalev after all: access opens and the row goes.
        $this->artisan('scalev:sync-purchases')->assertSuccessful();
        $this->assertNull(ScalevUnpaidOrder::find('U1'));
        $this->assertSame(1, Enrollment::count());
    }

    public function test_the_list_hides_buyers_who_since_bought_the_class_here(): void
    {
        ScalevUnpaidOrder::create(['order_id' => 'U1', 'customer_name' => 'Budi', 'email' => 'budi@example.test', 'course_id' => $this->inspeksi->id, 'ordered_at' => now()->subDay()]);
        $this->assertSame(1, ScalevUnpaidOrder::toFollowUp()->count());

        $student = Student::create(['name' => 'Budi', 'email' => 'budi@example.test']);
        Enrollment::create(['student_id' => $student->id, 'course_id' => $this->inspeksi->id, 'source' => EnrollmentSource::Doku, 'granted_at' => now()]);
        $this->assertSame(0, ScalevUnpaidOrder::toFollowUp()->count());
    }

    public function test_the_follow_up_message_sends_to_the_new_checkout_at_todays_price(): void
    {
        config(['lms.frontend_url' => 'https://academy.example.test']);
        $row = ScalevUnpaidOrder::create(['order_id' => 'U1', 'customer_name' => 'Budi Santoso', 'course_id' => $this->ac->id, 'amount' => 99000, 'ordered_at' => now()]);

        $message = ScalevUnpaidOrdersTable::message($row->load('course'));

        $this->assertStringContainsString('Halo Budi,', $message);
        $this->assertStringContainsString('https://academy.example.test/checkout/?kelas=kelas-online-ac&utm_source=whatsapp', $message);
        $this->assertStringContainsString('Rp149.000', $message);
        $this->assertStringContainsString('Tagihan yang lama tidak perlu dibayar', $message);
    }

    public function test_managers_see_the_list_and_consultants_do_not(): void
    {
        ScalevUnpaidOrder::create(['order_id' => 'U1', 'customer_name' => 'Budi Santoso', 'email' => 'budi@example.test', 'product_name' => 'Kelas AC Mobil', 'course_id' => $this->ac->id, 'ordered_at' => now()]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Consultant, 'is_active' => true]));
        $this->get('/admin/pesanan-scalev')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::Manager, 'is_active' => true]));
        $this->get('/admin/pesanan-scalev')->assertOk()->assertSee('Belum bayar di Scalev')->assertSee('Budi Santoso');
    }
}
