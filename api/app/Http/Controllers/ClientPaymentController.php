<?php

namespace App\Http\Controllers;

use App\Models\RentalClientPayment;
use App\Services\ClientBot\Invoice\InvoiceService;
use App\Services\ClientBot\Invoice\LiqPayGateway;
use Illuminate\View\View;

class ClientPaymentController extends Controller
{
    public function show(RentalClientPayment $payment, InvoiceService $invoices, LiqPayGateway $liqpay): View
    {
        $payment->load('client.vehicles', 'client.contracts', 'client.phones');
        $client = $payment->client;
        $invoice = $invoices->issueForPayment($client, $payment);
        $checkout = $liqpay->buildCheckoutPayload($payment, 'Платёж по договору аренды #'.$payment->id);

        return view('pay.client-payment', [
            'payment' => $payment,
            'client' => $client,
            'invoice' => $invoice,
            'checkout' => $checkout,
        ]);
    }
}
