<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use App\Enums\ExpenseCategory;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\Teacher;
use App\Models\TeacherBisyarahPayment;
use App\Models\User;
use App\Services\BisyarahExpenseService;
use App\Services\BookClosingService;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Database\Seeders\DummyGuruSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherBisyarahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        ActivityLog::query()->delete();
    }

    private function userWithRole(UserRole $role): User
    {
        return User::where('role', $role->value)->firstOrFail();
    }

    private function makeTeacher(string $name = 'Ust. Ahmad Fauzi', array $overrides = []): Teacher
    {
        return Teacher::create(array_merge([
            'nama_lengkap' => $name,
            'kota_asal' => 'Garut',
            'no_whatsapp' => '6281234567890',
            'status' => 'guru_tetap',
            'is_active' => true,
        ], $overrides));
    }

    public function test_bendahara_can_add_teacher_with_required_master_data(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $response = $this->actingAs($bendahara)->post(route('admin.teachers.store'), [
            'nama_lengkap' => 'Ust. Ahmad Fauzi',
            'kota_asal' => 'Garut',
            'no_whatsapp' => '0812-3456-7890',
            'status' => 'guru_tetap',
            'notes' => 'Pengampu kitab Jurumiyah.',
        ]);

        $response->assertRedirect(route('admin.teachers'));

        $teacher = Teacher::where('nama_lengkap', 'Ust. Ahmad Fauzi')->firstOrFail();
        $this->assertSame('6281234567890', $teacher->no_whatsapp);
        $this->assertSame('Garut', $teacher->kota_asal);
        $this->assertSame('guru_tetap', $teacher->status);
        $this->assertTrue($teacher->is_active);

        $page = $this->actingAs($bendahara)->get(route('admin.teachers'));
        $page->assertOk();
        $page->assertSee('Ust. Ahmad Fauzi');
        $page->assertSee('Garut');
        $page->assertSee('Guru Tetap');
        $page->assertSee('Bisyarah Bulanan');
    }

    public function test_teacher_form_requires_name_origin_whatsapp_and_status(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($bendahara)
            ->post(route('admin.teachers.store'), ['_form_context' => 'add'])
            ->assertSessionHasErrors(['nama_lengkap', 'kota_asal', 'no_whatsapp', 'status']);

        // Pesan kesalahan tampil di dalam popup Tambah Guru, bukan hanya di atas halaman.
        $this->actingAs($bendahara)
            ->from(route('admin.teachers'))
            ->followingRedirects()
            ->post(route('admin.teachers.store'), ['_form_context' => 'add'])
            ->assertOk()
            ->assertSee('Data guru belum tersimpan. Periksa isian berikut:')
            ->assertSee('Asal guru wajib diisi.')
            ->assertDontSee('Periksa kembali data Anda:');

        $this->assertSame(0, Teacher::count());
    }

    public function test_invalid_whatsapp_number_is_rejected(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->actingAs($bendahara)->post(route('admin.teachers.store'), [
            'nama_lengkap' => 'Ust. Nomor Salah',
            'kota_asal' => 'Bogor',
            'no_whatsapp' => '12345',
            'status' => 'staf',
        ])->assertSessionHasErrors('no_whatsapp');

        $this->assertSame(0, Teacher::count());
    }

    public function test_bendahara_can_update_and_deactivate_teacher(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher('Usth. Siti Aminah', [
            'kota_asal' => 'Bandung',
            'no_whatsapp' => '628111222333',
            'status' => 'guru_tidak_tetap',
        ]);

        $this->actingAs($bendahara)->patch(route('admin.teachers.update', $teacher), [
            'nama_lengkap' => 'Usth. Siti Aminah',
            'kota_asal' => 'Cimahi',
            'no_whatsapp' => '08111222333',
            'status' => 'guru_tetap',
            'notes' => 'Mulai mengajar kelas 8 putri.',
        ])->assertRedirect(route('admin.teachers'));

        $teacher->refresh();
        $this->assertSame('Cimahi', $teacher->kota_asal);
        $this->assertSame('guru_tetap', $teacher->status);
        $this->assertSame('628111222333', $teacher->no_whatsapp);

        // Menonaktifkan guru wajib disertai alasan.
        $this->actingAs($bendahara)
            ->patch(route('admin.teachers.toggle', $teacher))
            ->assertSessionHasErrors('inactive_reason');

        $this->assertTrue($teacher->fresh()->is_active);

        $this->actingAs($bendahara)
            ->patch(route('admin.teachers.toggle', $teacher), ['inactive_reason' => 'Pindah tugas ke pesantren lain.'])
            ->assertRedirect();

        $teacher->refresh();
        $this->assertFalse($teacher->is_active);
        $this->assertSame('Pindah tugas ke pesantren lain.', $teacher->inactive_reason);
        $this->assertNotNull($teacher->inactivated_at);

        $this->actingAs($bendahara)
            ->get(route('admin.teachers'))
            ->assertOk()
            ->assertSee('Pindah tugas ke pesantren lain.')
            ->assertDontSee('fa-money-bill-wave mr-1"></i> Bisyarah', false);

        // Mengaktifkan kembali menghapus alasan nonaktif.
        $this->actingAs($bendahara)
            ->patch(route('admin.teachers.toggle', $teacher))
            ->assertRedirect();

        $teacher->refresh();
        $this->assertTrue($teacher->is_active);
        $this->assertNull($teacher->inactive_reason);
        $this->assertNull($teacher->inactivated_at);

        $this->actingAs($bendahara)
            ->patch(route('admin.teachers.toggle', $teacher), ['inactive_reason' => 'Berhenti mengajar.'])
            ->assertRedirect();

        $this->assertFalse($teacher->fresh()->is_active);

        $this->actingAs($bendahara)->get(route('admin.teachers'))
            ->assertOk()
            ->assertSee('Nonaktif');
    }

    public function test_teacher_search_and_status_filter_work(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);

        $this->makeTeacher('Ust. Muhammad Ridwan', [
            'kota_asal' => 'Tasikmalaya',
            'no_whatsapp' => '6281111111111',
            'status' => 'pengasuh',
        ]);

        $this->makeTeacher('Ust. Abdul Hakim', [
            'kota_asal' => 'Ciamis',
            'no_whatsapp' => '6282222222222',
            'status' => 'staf',
        ]);

        $this->actingAs($bendahara)->get(route('admin.teachers', ['search' => 'Ridwan']))
            ->assertOk()
            ->assertSee('Ust. Muhammad Ridwan')
            ->assertDontSee('Ust. Abdul Hakim');

        $this->actingAs($bendahara)->get(route('admin.teachers', ['status' => 'staf']))
            ->assertOk()
            ->assertSee('Ust. Abdul Hakim')
            ->assertDontSee('Ust. Muhammad Ridwan');
    }

    public function test_bendahara_records_bisyarah_each_month_and_reuses_teacher_data(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => '2026-10',
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'BSI',
            'bank_account_number' => '7212345678',
            'bank_account_name' => 'Ahmad Fauzi',
            'notes' => 'Bisyarah Oktober.',
        ])->assertRedirect(route('admin.bisyarah', ['period' => '2026-10']));

        $october = TeacherBisyarahPayment::where('teacher_id', $teacher->id)->firstOrFail();
        $this->assertSame('1500000.00', $october->amount);
        $this->assertSame('transfer', $october->payment_method);
        $this->assertSame('BSI', $october->bank_name);
        $this->assertSame('7212345678', $october->bank_account_number);

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => '2026-11',
            'amount' => 1600000,
            'payment_method' => 'cash',
            'transfer_channel' => 'BCA',
            'bank_account_number' => '99887766',
            'bank_account_name' => 'Ahmad Fauzi',
        ])->assertRedirect(route('admin.bisyarah', ['period' => '2026-11']));

        $november = TeacherBisyarahPayment::where('teacher_id', $teacher->id)
            ->where('period_month', 11)
            ->firstOrFail();

        $this->assertSame('1600000.00', $november->amount);
        $this->assertSame('cash', $november->payment_method);
        $this->assertNull($november->bank_name);
        $this->assertNull($november->bank_account_number);
        $this->assertNull($november->bank_account_name);

        // Data guru tetap satu baris meski sudah dipakai dua bulan.
        $this->assertSame(1, Teacher::count());

        $this->actingAs($bendahara)
            ->get(route('admin.bisyarah', ['period' => '2026-10']))
            ->assertOk()
            ->assertSee('Ust. Ahmad Fauzi')
            ->assertSee('Rp 1.500.000')
            ->assertSee('BSI');

        $this->actingAs($bendahara)
            ->get(route('admin.bisyarah', ['period' => '2026-11']))
            ->assertOk()
            ->assertSee('Rp 1.600.000')
            ->assertSee('Tunai');

        $this->assertSame(2, ActivityLog::where('module', ActivityModule::Bisyarah->value)->count());
    }

    public function test_transfer_requires_bank_details_and_duplicate_month_is_rejected(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => '2026-10',
            'amount' => 1500000,
            'payment_method' => 'transfer',
        ])->assertSessionHasErrors(['transfer_channel', 'bank_account_number']);

        $this->assertSame(0, TeacherBisyarahPayment::count());

        $payload = [
            'teacher_id' => $teacher->id,
            'period' => '2026-10',
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'BSI',
            'bank_account_number' => '7212345678',
        ];

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), $payload)->assertRedirect();
        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), $payload)
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame(1, TeacherBisyarahPayment::count());
    }

    public function test_nominal_and_payment_method_are_required(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => '2026-10',
        ])->assertSessionHasErrors(['amount', 'payment_method']);

        $this->assertSame(0, TeacherBisyarahPayment::count());
    }

    public function test_bendahara_can_correct_bisyarah_and_cash_clears_bank_details(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $payment = TeacherBisyarahPayment::create([
            'teacher_id' => $teacher->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'BSI',
            'bank_account_number' => '7212345678',
            'bank_account_name' => 'Ahmad Fauzi',
            'recorded_by' => $bendahara->id,
        ]);

        $this->actingAs($bendahara)->patch(route('admin.bisyarah.update', $payment), [
            'amount' => 1750000,
            'payment_method' => 'cash',
        ])->assertRedirect(route('admin.bisyarah', ['period' => '2026-10']));

        $payment->refresh();
        $this->assertSame('1750000.00', $payment->amount);
        $this->assertSame('cash', $payment->payment_method);
        $this->assertNull($payment->bank_name);
        $this->assertNull($payment->bank_account_number);
        $this->assertNull($payment->bank_account_name);

        $log = ActivityLog::where('module', ActivityModule::Bisyarah->value)
            ->where('action', ActivityAction::Updated->value)
            ->sole();

        $this->assertStringContainsString('Memperbaiki bisyarah', $log->description);
        $this->assertArrayHasKey('Nominal Bisyarah', $log->properties);
    }

    public function test_bisyarah_is_locked_when_the_month_is_closed(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $closedTeacher = $this->makeTeacher('Ust. Sudah Dibayar');
        $otherTeacher = $this->makeTeacher('Ust. Belum Dibayar', [
            'no_whatsapp' => '6283333333333',
        ]);

        $period = now()->format('Y-m');
        $payment = TeacherBisyarahPayment::create([
            'teacher_id' => $closedTeacher->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'amount' => 1500000,
            'payment_method' => 'cash',
            'recorded_by' => $bendahara->id,
        ]);

        app(BookClosingService::class)->close(now()->year, now()->month, $bendahara->id);

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $otherTeacher->id,
            'period' => $period,
            'amount' => 1200000,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('period');

        $this->actingAs($bendahara)->patch(route('admin.bisyarah.update', $payment), [
            'amount' => 2000000,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('period');

        $this->assertSame(1, TeacherBisyarahPayment::count());
        $this->assertSame('1500000.00', $payment->fresh()->amount);
    }

    public function test_recording_bisyarah_creates_linked_expense_in_cash_flow(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();
        $period = now()->format('Y-m');

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => $period,
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'BSI',
            'bank_account_number' => '7212345678',
            'bank_account_name' => 'Ahmad Fauzi',
        ])->assertRedirect();

        $payment = TeacherBisyarahPayment::with('expense')->sole();
        $expense = $payment->expense;

        $this->assertNotNull($expense);
        $this->assertSame(ExpenseCategory::Gaji, $expense->category);
        $this->assertSame('1500000.00', $expense->amount);
        $this->assertSame('transfer', $expense->payment_method);
        $this->assertSame('BSI', $expense->transfer_channel);
        $this->assertSame('Transfer BSI', $expense->paymentMethodLabel());
        $this->assertSame('Ust. Ahmad Fauzi', $expense->recipient);
        $this->assertSame(now()->toDateString(), $expense->expense_date->toDateString());
        $this->assertStringContainsString('Bisyarah Ust. Ahmad Fauzi', $expense->description);
        $this->assertStringContainsString('BSI 7212345678', (string) $expense->notes);
        $this->assertSame($bendahara->id, $expense->recorded_by);

        $flow = app(CashFlowService::class)->build(now()->startOfMonth(), now()->endOfMonth());
        $entry = $flow['entries']->firstWhere('code', $expense->expense_code);

        $this->assertNotNull($entry);
        $this->assertSame('keluar', $entry['direction']);
        $this->assertSame(1500000.0, $entry['amount']);

        $this->actingAs($bendahara)
            ->get(route('admin.reports.cash-flow', [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($expense->expense_code)
            ->assertSee('Bisyarah Ust. Ahmad Fauzi');

        $this->actingAs($bendahara)
            ->get(route('admin.bisyarah', ['period' => $period]))
            ->assertOk()
            ->assertSee($expense->expense_code);

        $log = ActivityLog::where('module', ActivityModule::Bisyarah->value)->sole();
        $this->assertSame($expense->expense_code, $log->properties['Kas Keluar']);
    }

    public function test_past_period_expense_is_dated_within_that_month(): void
    {
        $service = app(BisyarahExpenseService::class);
        $today = Carbon::create(2026, 11, 15);

        $this->assertSame('2026-10-31', $service->expenseDateFor(2026, 10, $today)->toDateString());
        $this->assertSame('2026-11-15', $service->expenseDateFor(2026, 11, $today)->toDateString());
        $this->assertSame('2026-12-01', $service->expenseDateFor(2026, 12, $today)->toDateString());
    }

    public function test_correcting_bisyarah_updates_its_expense(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => now()->format('Y-m'),
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'BSI',
            'bank_account_number' => '7212345678',
        ])->assertRedirect();

        $payment = TeacherBisyarahPayment::sole();
        $originalCode = $payment->expense->expense_code;

        $this->actingAs($bendahara)->patch(route('admin.bisyarah.update', $payment), [
            'amount' => 1750000,
            'payment_method' => 'cash',
            'notes' => 'Dibayar tunai.',
        ])->assertRedirect();

        $expense = $payment->fresh()->expense;

        $this->assertSame(1, Expense::where('category', ExpenseCategory::Gaji->value)->whereHas('bisyarahPayment')->count());
        $this->assertSame($originalCode, $expense->expense_code);
        $this->assertSame('1750000.00', $expense->amount);
        $this->assertSame('cash', $expense->payment_method);
        $this->assertNull($expense->transfer_channel);
        $this->assertSame('Dibayar tunai.', $expense->notes);
        $this->assertFalse($expense->isVoided());
    }

    public function test_transfer_can_go_through_a_digital_wallet_or_a_channel_typed_under_lainnya(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => '2026-10',
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'DANA',
            'bank_account_number' => '081234567890',
        ])->assertRedirect(route('admin.bisyarah', ['period' => '2026-10']));

        $payment = TeacherBisyarahPayment::sole();
        $this->assertSame('DANA', $payment->bank_name);
        $this->assertSame('DANA', $payment->expense->transfer_channel);
        $this->assertSame('Transfer DANA', $payment->paymentChannelLabel());

        $this->actingAs($bendahara)
            ->get(route('admin.bisyarah', ['period' => '2026-10']))
            ->assertOk()
            ->assertSee('Transfer DANA')
            ->assertSee('081234567890');

        // Lainnya tanpa nama saluran ditolak, saluran di luar daftar ditolak.
        $this->actingAs($bendahara)->patch(route('admin.bisyarah.update', $payment), [
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'Lainnya',
            'bank_account_number' => '081234567890',
        ])->assertSessionHasErrors('transfer_channel_other');

        $this->actingAs($bendahara)->patch(route('admin.bisyarah.update', $payment), [
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'Bank Antah Berantah',
            'bank_account_number' => '081234567890',
        ])->assertSessionHasErrors('transfer_channel');

        $this->actingAs($bendahara)->patch(route('admin.bisyarah.update', $payment), [
            'amount' => 1500000,
            'payment_method' => 'transfer',
            'transfer_channel' => 'Lainnya',
            'transfer_channel_other' => '  Bank Jatim ',
            'bank_account_number' => '081234567890',
        ])->assertRedirect();

        $payment->refresh();
        $this->assertSame('Bank Jatim', $payment->bank_name);
        $this->assertSame('Bank Jatim', $payment->expense->transfer_channel);

        $log = ActivityLog::where('action', ActivityAction::Updated->value)->latest('id')->firstOrFail();
        $this->assertSame('Transfer DANA → Transfer Bank Jatim', $log->properties['Metode']);
    }

    public function test_cancelling_bisyarah_voids_its_expense_and_frees_the_month(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();
        $period = now()->format('Y-m');

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => $period,
            'amount' => 1500000,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $payment = TeacherBisyarahPayment::sole();
        $expense = $payment->expense;

        $this->actingAs($bendahara)
            ->delete(route('admin.bisyarah.destroy', $payment), [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($bendahara)
            ->delete(route('admin.bisyarah.destroy', $payment), ['reason' => 'Salah pilih guru.'])
            ->assertRedirect(route('admin.bisyarah', ['period' => $period]));

        $this->assertSame(0, TeacherBisyarahPayment::count());

        $expense->refresh();
        $this->assertTrue($expense->isVoided());
        $this->assertSame('Salah pilih guru.', $expense->void_reason);
        $this->assertSame($bendahara->id, $expense->voided_by);

        $flow = app(CashFlowService::class)->build(now()->startOfMonth(), now()->endOfMonth());
        $this->assertNull($flow['entries']->firstWhere('code', $expense->expense_code));

        $log = ActivityLog::where('module', ActivityModule::Bisyarah->value)
            ->where('action', ActivityAction::Cancelled->value)
            ->sole();
        $this->assertStringContainsString('Salah pilih guru.', $log->description);
        $this->assertSame($expense->expense_code, $log->properties['Kas Keluar Dibatalkan']);

        // Bulan yang sama bisa dicatat ulang setelah dibatalkan.
        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => $period,
            'amount' => 1400000,
            'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, TeacherBisyarahPayment::count());
        $this->assertSame(1, Expense::notVoided()->whereHas('bisyarahPayment')->count());
    }

    public function test_cancelling_bisyarah_is_locked_when_the_month_is_closed(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => now()->format('Y-m'),
            'amount' => 1500000,
            'payment_method' => 'cash',
        ])->assertRedirect();

        app(BookClosingService::class)->close(now()->year, now()->month, $bendahara->id);

        $payment = TeacherBisyarahPayment::sole();

        $this->actingAs($bendahara)
            ->delete(route('admin.bisyarah.destroy', $payment), ['reason' => 'Terlambat.'])
            ->assertSessionHasErrors('period');

        $this->assertSame(1, TeacherBisyarahPayment::count());
        $this->assertFalse($payment->expense->fresh()->isVoided());
    }

    public function test_bisyarah_expense_cannot_be_voided_from_expense_page(): void
    {
        $bendahara = $this->userWithRole(UserRole::Bendahara);
        $teacher = $this->makeTeacher();

        $this->actingAs($bendahara)->post(route('admin.bisyarah.store'), [
            'teacher_id' => $teacher->id,
            'period' => now()->format('Y-m'),
            'amount' => 1500000,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $expense = TeacherBisyarahPayment::sole()->expense;

        $this->actingAs($bendahara)
            ->patch(route('admin.expenses.void', $expense), ['void_reason' => 'Coba batalkan.'])
            ->assertSessionHasErrors('void_reason');

        $this->assertFalse($expense->fresh()->isVoided());

        $this->actingAs($bendahara)
            ->get(route('admin.expenses', [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($expense->expense_code)
            ->assertSee(route('admin.bisyarah', ['period' => now()->format('Y-m')]), false);
    }

    public function test_dummy_guru_seeder_builds_teachers_with_synced_expenses(): void
    {
        $this->seed(DummyGuruSeeder::class);

        $this->assertSame(15, Teacher::count());
        $this->assertSame(45, TeacherBisyarahPayment::count());
        $this->assertSame(0, TeacherBisyarahPayment::whereNull('expense_id')->count());
        $this->assertSame(45, Expense::where('category', ExpenseCategory::Gaji->value)->notVoided()->count());
        $this->assertSame(
            (float) TeacherBisyarahPayment::sum('amount'),
            (float) Expense::where('category', ExpenseCategory::Gaji->value)->notVoided()->sum('amount'),
        );

        $teacher = Teacher::where('nama_lengkap', 'Raihan Ibnu Rusydi')->sole();
        $this->assertNotSame('', $teacher->kota_asal);
        $this->assertMatchesRegularExpression('/^628[0-9]{7,11}$/', $teacher->no_whatsapp);

        $current = TeacherBisyarahPayment::forPeriod(now()->year, now()->month)
            ->where('teacher_id', $teacher->id)
            ->sole();
        $this->assertSame('1200000.00', $current->amount);
        $this->assertSame('transfer', $current->payment_method);
        $this->assertNotNull($current->bank_account_number);

        // Seeder aman dijalankan ulang: tidak menggandakan data.
        $this->seed(DummyGuruSeeder::class);
        $this->assertSame(15, Teacher::count());
        $this->assertSame(45, Expense::where('category', ExpenseCategory::Gaji->value)->count());
    }

    public function test_pengasuh_cannot_manage_teacher_data(): void
    {
        $pengasuh = $this->userWithRole(UserRole::Pengasuh);

        $this->actingAs($pengasuh)
            ->post(route('admin.teachers.store'), [
                'nama_lengkap' => 'Tidak Boleh',
                'kota_asal' => 'Bogor',
                'no_whatsapp' => '6281234567890',
                'status' => 'staf',
            ])
            ->assertForbidden();

        // Pengasuh boleh melihat halaman bisyarah, tetapi tidak mencatat.
        $this->actingAs($pengasuh)
            ->get(route('admin.bisyarah'))
            ->assertOk()
            ->assertSee('readonly-mode', false);

        $this->actingAs($pengasuh)
            ->post(route('admin.bisyarah.store'), [
                'teacher_id' => $this->makeTeacher()->id,
                'period' => now()->format('Y-m'),
                'amount' => 1000000,
                'payment_method' => 'cash',
            ])
            ->assertForbidden();

        $this->assertSame(0, TeacherBisyarahPayment::count());
    }
}
