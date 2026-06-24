<?php

namespace App\Services\ClientBot\Invoice;

use App\Models\RentalClientPayment;

class LiqPayGateway
{
    public function buildPaymentUrl(RentalClientPayment $payment): string
    {
        $base = config('client_bot.payment_url_base') ?: rtrim((string) config('app.url'), '/').'/pay';

        return $base.'/'.$payment->id;
    }

    /** @return array<string, mixed>|null */
    public function buildCheckoutPayload(RentalClientPayment $payment, string $description): ?array
    {
        if (! config('client_bot.liqpay.enabled')) {
            return null;
        }

        $public = config('client_bot.liqpay.public_key');
        $private = config('client_bot.liqpay.private_key');
        if (! $public || ! $private) {
            return null;
        }

        $params = [
            'public_key' => $public,
            'version' => 3,
            'action' => 'pay',
            'amount' => (float) $payment->amount,
            'currency' => 'UAH',
            'description' => $description,
            'order_id' => 'payment-'.$payment->id,
            'result_url' => $this->buildPaymentUrl($payment),
            'sandbox' => config('client_bot.liqpay.sandbox', true) ? 1 : 0,
        ];

        $data = base64_encode(json_encode($params, JSON_UNESCAPED_UNICODE));
        $signature = base64_encode(sha1($private.$data.$private, true));

        return [
            'data' => $data,
            'signature' => $signature,
            'url' => 'https://www.liqpay.ua/api/3/checkout',
        ];
    }
}
