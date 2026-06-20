<?php

namespace App\Services;

class SoapAuditService
{
    protected $iaeCloud;

    public function __construct(IaeCloudService $iaeCloud)
    {
        $this->iaeCloud = $iaeCloud;
    }

    /**
     * Send audit SOAP log supporting both:
     * - sendAudit($actionName, $data)
     * - sendAudit($teamId, $actionName, $data)
     */
    public function sendAudit(...$args)
    {
        if (count($args) === 3) {
            // Called with ($teamId, $actionName, $data)
            $actionName = $args[1];
            $data = $args[2];
        } else {
            // Called with ($actionName, $data)
            $actionName = $args[0] ?? 'InventoryAction';
            $data = $args[1] ?? [];
        }

        $dataArray = is_string($data) ? json_decode($data, true) : $data;
        if (!is_array($dataArray)) {
            $dataArray = ['data' => $data];
        }

        return $this->iaeCloud->sendSoapAudit($actionName, $dataArray);
    }
}