<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IaeCloudService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $nim;
    protected string $teamId;
    protected string $exchange;

    public function __construct()
    {
        $this->baseUrl = config('services.iae_cloud.base_url', 'https://iae-sso.virtualfri.id');
        $this->apiKey = config('services.iae_cloud.api_key', 'KEY-MHS-133');
        $this->nim = config('services.iae_cloud.nim', '102022400047');
        $this->teamId = config('services.iae_cloud.team_id', 'TEAM-03');
        $this->exchange = config('services.iae_cloud.exchange', 'iae.central.exchange');
    }

    /**
     * Get M2M Token from SSO Server.
     */
    public function getToken(): ?string
    {
        try {
            $response = Http::withoutVerifying()->post($this->baseUrl . '/api/v1/auth/token', [
                'api_key' => $this->apiKey,
                'nim' => $this->nim,
            ]);

            if (!$response->successful()) {
                Log::error("Failed to fetch M2M Token. Status: " . $response->status() . " Response: " . $response->body());
                return null;
            }

            return $response->json('token') ?? $response->json('access_token');
        } catch (\Exception $e) {
            Log::error("Exception when fetching M2M Token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send SOAP XML Audit Log.
     */
    public function sendSoapAudit(string $activityName, array $logData): ?string
    {
        $token = $this->getToken();
        if (!$token) {
            Log::error("Cannot send SOAP audit: token is null.");
            return null;
        }

        $logContent = json_encode($logData);

        // XML Payload without invalid spaces
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>{$this->teamId}</iae:TeamID>
      <iae:ActivityName>{$activityName}</iae:ActivityName>
      <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>
XML;

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                ])
                ->send('POST', $this->baseUrl . '/soap/v1/audit', [
                    'body' => $xml,
                ]);

            if (!$response->successful()) {
                Log::error("SOAP Audit failed. Status: " . $response->status() . " Body: " . $response->body());
                return null;
            }

            $xmlResult = $response->body();
            
            // Clean namespace prefixes to parse easily
            $cleanXml = str_ireplace(['soap:', 'iae:'], '', $xmlResult);
            $xmlElement = simplexml_load_string($cleanXml);
            $receiptNumber = (string) ($xmlElement->Body->AuditResponse->ReceiptNumber ?? '');

            if (empty($receiptNumber)) {
                if (preg_match('/<ReceiptNumber>(.*?)<\/ReceiptNumber>/s', $xmlResult, $matches)) {
                    $receiptNumber = $matches[1];
                } elseif (preg_match('/<iae:ReceiptNumber>(.*?)<\/iae:ReceiptNumber>/s', $xmlResult, $matches)) {
                    $receiptNumber = $matches[1];
                }
            }

            return empty($receiptNumber) ? null : $receiptNumber;

        } catch (\Exception $e) {
            Log::error("Exception in SOAP Audit: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Publish JSON Event to RabbitMQ via HTTP REST.
     */
    public function publishRabbitMq(string $routingKey, array $payload): bool
    {
        $token = $this->getToken();
        if (!$token) {
            Log::error("Cannot publish RabbitMQ message: token is null.");
            return false;
        }

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->post($this->baseUrl . '/api/v1/messages/publish', [
                    'exchange' => $this->exchange,
                    'routing_key' => $routingKey,
                    'payload' => $payload,
                ]);

            if (!$response->successful()) {
                Log::error("Failed to publish RabbitMQ message. Status: " . $response->status() . " Body: " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Exception in RabbitMQ Publish: " . $e->getMessage());
            return false;
        }
    }
}
