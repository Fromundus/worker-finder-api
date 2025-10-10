<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoceanService
{
    protected $apiToken;
    protected $from;

    public function __construct()
    {
        // You can hardcode these since env() is giving issues
        $this->apiToken = 'apit-sEPV2sjAhHNbbL24bOzijK56TGS3MVH4-nmNgC';
        $this->from = 'WorkerFinder';
    }

    /**
     * Send an SMS via Mocean API.
     *
     * @param string $to Phone number in 09XXXXXXXXX format
     * @param string $message
     * @return array|null
     */
    public function sendSms(string $to, string $message): ?array
    {
        $formattedNumber = $this->formatNumber($to);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->asForm()->post('https://rest.moceanapi.com/rest/2/sms', [
            'mocean-from' => $this->from,
            'mocean-to' => $formattedNumber,
            'mocean-text' => $message,
        ]);

        if ($response->failed()) {
            return [
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        }

        return $response->json();
    }

    /**
     * Convert 09XXXXXXXXX to 639XXXXXXXXX format
     */
    private function formatNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number); // remove non-digits
        if (str_starts_with($number, '09')) {
            $number = '63' . substr($number, 1);
        }
        return $number;
    }
}
