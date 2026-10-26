<?php

namespace Tests\Feature;

use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Tamu tidak boleh mengunduh kuitansi hanya dengan menebak id.
     */
    public function test_guest_cannot_download_receipt(): void
    {
        $receipt = Receipt::firstOrFail();

        $this->get(route('receipt.download', $receipt->id))->assertNotFound();
    }

    /**
     * Wali santri hanya bisa mengunduh kuitansi milik anaknya sendiri.
     */
    public function test_portal_guardian_can_only_download_own_child_receipt(): void
    {
        $receipt = Receipt::firstOrFail();
        $ownerId = (int) $receipt->payment->student_id;
        $otherStudentId = (int) Student::where('id', '!=', $ownerId)->value('id');

        $this->withSession(['portal_student_id' => $ownerId])
            ->get(route('receipt.download', $receipt->id))
            ->assertOk();

        $this->withSession(['portal_student_id' => $otherStudentId])
            ->get(route('receipt.download', $receipt->id))
            ->assertNotFound();
    }

    /**
     * Pengurus aktif tetap bisa mengunduh kuitansi dari panel admin.
     */
    public function test_active_staff_can_download_receipt(): void
    {
        $receipt = Receipt::firstOrFail();

        $this->actingAs(User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail())
            ->get(route('receipt.download', $receipt->id))
            ->assertOk();
    }

    /**
     * Akun pengurus yang dinonaktifkan tidak mendapat akses lewat sesi lama.
     */
    public function test_inactive_staff_cannot_download_receipt(): void
    {
        $receipt = Receipt::firstOrFail();
        $user = User::where('email', 'bendahara1@ptqamsa.id')->firstOrFail();
        $user->is_active = false;

        $this->actingAs($user)
            ->get(route('receipt.download', $receipt->id))
            ->assertNotFound();
    }
}
