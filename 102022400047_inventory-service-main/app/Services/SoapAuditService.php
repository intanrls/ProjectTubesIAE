<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SoapAuditService
{
    public function sendAudit($actionName, $data)
    {
        // 1. Persiapkan data log
        $logContent = is_array($data) ? json_encode($data) : $data;

        // 2. Susun XML Envelope (Wajib menggunakan CDATA untuk data JSON)
        // Pastikan namespace 'iae' sesuai dengan skema dosen
        $xmlBody = '<?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
            <soap:Body>
                <iae: AuditRequest>
                    <iae: TeamID>TEAM-03</iae: TeamID>
                    <iae: ActivityName>' . $actionName . '</iae: ActivityName>
                    <iae: LogContent><![CDATA[' . $logContent . ']]></iae: LogContent>
                </iae: AuditRequest>
            </soap:Body>
        </soap:Envelope>';

        // 3. Kirim ke Endpoint Dosen
        // Pastikan Anda sudah memiliki token yang valid (dari /api/v1/auth/token)
        $token = session('api_token'); 

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'text/xml'])
            ->post('https://iae-sso.virtualfri.id/soap/v1/audit', $xmlBody);

        // 4. Proses Respons & Ambil Receipt Number
        if ($response->successful()) {
            $xmlResponse = $response->body();
            
            // Mengambil isi dari tag <iae: Receipt Number> menggunakan Regex
            preg_match('/<iae: Receipt Number>(.*?)<\/iae: Receipt Number>/', $xmlResponse, $matches);
            
            return $matches[1] ?? 'FAILED-TO-EXTRACT';
        }

        // Jika gagal, catat di log agar mudah di-debug
        Log::error("SOAP Error (TEAM-03): " . $response->body());
        return null;
    }
}