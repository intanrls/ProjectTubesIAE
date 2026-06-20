<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IaeCloudService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $nim;
    protected string $teamId;
    protected string $exchange;

    public function __construct()
    {
        $this->baseUrl = config('services.iae_cloud.base_url');
        $this->apiKey = config('services.iae_cloud.api_key');
        $this->nim = config('services.iae_cloud.nim');
        $this->teamId = config('services.iae_cloud.team_id');
        $this->exchange = config('services.iae_cloud.exchange');
    }

    public function getToken(): ?string
    {
        $response = Http::withoutVerifying()->post($this->baseUrl . '/api/v1/auth/token', [
            'api_key' => $this->apiKey,
            'nim' => $this->nim,
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('token');
    }

    public function sendSoapAudit(array $expedition): ?string
    {
        $token = $this->getToken();

        if (!$token) {
            return null;
        }

        $logContent = json_encode($expedition);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>{$this->teamId}</iae:TeamID>
      <iae:ActivityName>ExpeditionCreated</iae:ActivityName>
      <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>
XML;

       $response = Http::withoutVerifying()
    ->withToken($token)
    ->withHeaders([
        'Content-Type' => 'text/xml',
    ])
    ->send('POST', $this->baseUrl . '/soap/v1/audit', [
        'body' => $xml,
    ]);

        if (!$response->successful()) {
            return null;
        }

        $xmlResult = $response->body();
        $receiptNumber = null;

        try {
            $cleanXml = str_ireplace(['soap:', 'iae:'], '', $xmlResult);
            $xmlElement = simplexml_load_string($cleanXml);
            $receiptNumber = (string) ($xmlElement->Body->AuditResponse->ReceiptNumber ?? '');
        } catch (\Exception $e) {
            // Fallback to regex on XML parse error
        }

        if (empty($receiptNumber)) {
            if (preg_match('/<iae:ReceiptNumber>(.*?)<\/iae:ReceiptNumber>/s', $xmlResult, $matches)) {
                $receiptNumber = trim($matches[1]);
            } elseif (preg_match('/<ReceiptNumber>(.*?)<\/ReceiptNumber>/s', $xmlResult, $matches)) {
                $receiptNumber = trim($matches[1]);
            }
        }

        return empty($receiptNumber) ? null : $receiptNumber;
    }

    public function publishRabbitMq(array $expedition): bool
    {
        $token = $this->getToken();

        if (!$token) {
            return false;
        }

$response = Http::withoutVerifying()->withToken($token)->post($this->baseUrl . '/api/v1/messages/publish', [
            'exchange' => $this->exchange,
            'routing_key' => 'expedition.created',
            'payload' => [
                'team_id' => $this->teamId,
                'service' => 'Expedition-Service',
                'activity' => 'ExpeditionCreated',
                'event' => 'expedition.created',
                'data' => $expedition,
            ],
        ]);

        return $response->successful();
    }
}