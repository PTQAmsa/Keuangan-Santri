<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController
{
    /**
     * Unduh file PDF Kuitansi Resmi.
     *
     * Dokumen hanya boleh diakses oleh pengurus yang login atau wali santri
     * pemilik kuitansi (session portal). Tanpa salah satu syarat itu, balas 404
     * agar keberadaan kuitansi tidak bisa ditebak dari nomor id.
     */
    public function download(Request $request, int $id, ReceiptService $receiptService)
    {
        $receipt = Receipt::with(['payment.student', 'payment.invoice.category'])->findOrFail($id);

        abort_unless($this->canAccess($request, $receipt), 404);

        $pdf = $receiptService->generateReceiptPdf($receipt);

        $filename = 'Kuitansi-'.str_replace('/', '-', $receipt->receipt_number).'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Cek hak akses terhadap satu kuitansi.
     */
    private function canAccess(Request $request, Receipt $receipt): bool
    {
        $user = $request->user();

        if ($user !== null && $user->is_active) {
            return true;
        }

        // Wali santri hanya boleh mengunduh kuitansi anaknya sendiri.
        return (int) $request->session()->get('portal_student_id') === (int) $receipt->payment->student_id;
    }
}
