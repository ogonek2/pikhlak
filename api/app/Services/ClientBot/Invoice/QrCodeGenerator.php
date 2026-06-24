<?php

namespace App\Services\ClientBot\Invoice;

use App\Models\RentalClient;
use App\Models\RentalClientPayment;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeGenerator
{
    public function generate(string $content, string $filename): string
    {
        $relative = 'invoices/qr/'.ltrim($filename, '/');
        $absolute = Storage::disk('local')->path($relative);
        $dir = dirname($absolute);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($content)
            ->size(280)
            ->margin(8)
            ->build();

        $result->saveToFile($absolute);

        return $relative;
    }
}
