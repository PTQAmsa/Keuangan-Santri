<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\ReceiptService;

class ReceiptController
{
    /**
     * Unduh file PDF Kuitansi Resmi
     */
    public function download(int $id, ReceiptService $receiptService)
    {
        $receipt = Receipt::with(['payment.student', 'payment.invoice.category'])->findOrFail($id);

        $pdf = $receiptService->generateReceiptPdf($receipt);

        $filename = 'Kuitansi-' . str_replace('/', '-', $receipt->receipt_number) . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Halaman Publik Verifikasi QR Code Keaslian Kuitansi
     */
    public function verify(string $token)
    {
        $receipt = Receipt::with(['payment.student', 'payment.invoice.category', 'payment.recordedBy'])
            ->where('qr_verification_token', $token)
            ->first();

        return view('receipt.verify', compact('receipt', 'token'));
    }
}
