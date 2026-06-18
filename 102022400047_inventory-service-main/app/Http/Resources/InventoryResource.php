<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'item_name' => $this->nama_barang, // Sesuai DB
            'stock' => (int) $this->stok,      // Sesuai DB
            'status_ketersediaan' => $this->status_qc ?? 'UNKNOWN',
        ];
    }
}