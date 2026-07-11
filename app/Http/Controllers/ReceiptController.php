<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\ReceiptService;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Payment $payment, ReceiptService $receipts): View
    {
        abort_unless((int) $payment->user_id === (int) auth()->id() || auth()->user()?->isAdmin(), 403);
        abort_unless($payment->status === 'completed', 404);

        return view('payments.receipt', $receipts->receiptPayload($payment));
    }
}
