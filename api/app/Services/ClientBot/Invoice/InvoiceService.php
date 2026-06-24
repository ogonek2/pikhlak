<?php

namespace App\Services\ClientBot\Invoice;

use App\Models\RentalClient;
use App\Models\RentalClientInvoice;
use App\Models\RentalClientPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        private readonly LiqPayGateway $liqpay,
        private readonly QrCodeGenerator $qr,
    ) {}

    public function issueForPayment(RentalClient $client, RentalClientPayment $payment): RentalClientInvoice
    {
        $existing = RentalClientInvoice::query()
            ->where('rental_client_payment_id', $payment->id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existing) {
            return $existing;
        }

        $contract = $client->activeContract();
        $currency = $contract?->currency ?? 'UAH';
        $paymentUrl = $this->liqpay->buildPaymentUrl($payment);
        $invoiceNumber = 'INV-'.now()->format('Ym').'-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);

        $qrRelative = $this->qr->generate($paymentUrl, $invoiceNumber.'.png');
        $pdfRelative = $this->renderPdf($client, $payment, $invoiceNumber, $currency, $paymentUrl, $qrRelative);

        return RentalClientInvoice::query()->create([
            'rental_client_id' => $client->id,
            'rental_client_payment_id' => $payment->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $payment->amount,
            'currency' => $currency,
            'pdf_path' => $pdfRelative,
            'qr_path' => $qrRelative,
            'payment_url' => $paymentUrl,
            'status' => 'issued',
            'issued_at' => now(),
        ]);
    }

    private function renderPdf(
        RentalClient $client,
        RentalClientPayment $payment,
        string $invoiceNumber,
        string $currency,
        string $paymentUrl,
        string $qrRelative,
    ): string {
        $relative = 'invoices/pdf/'.$invoiceNumber.'.pdf';
        $absolute = Storage::disk('local')->path($relative);
        $dir = dirname($absolute);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $vehicle = $client->currentVehicle();
        $symbol = config('client_bot.currency_symbols.'.$currency, $currency);

        $pdf = Pdf::loadView('pdf.client-invoice', [
            'client' => $client,
            'payment' => $payment,
            'invoiceNumber' => $invoiceNumber,
            'currency' => $currency,
            'symbol' => $symbol,
            'paymentUrl' => $paymentUrl,
            'vehicleLabel' => $vehicle ? trim($vehicle->make.' '.$vehicle->model) : '—',
            'qrAbsolute' => Storage::disk('local')->path($qrRelative),
        ]);

        file_put_contents($absolute, $pdf->output());

        return $relative;
    }

    public function absolutePath(?string $relative): ?string
    {
        if (! $relative) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }
}
