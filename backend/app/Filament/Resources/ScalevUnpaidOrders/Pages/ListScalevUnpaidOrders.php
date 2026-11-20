<?php

namespace App\Filament\Resources\ScalevUnpaidOrders\Pages;

use App\Filament\Resources\ScalevUnpaidOrders\ScalevUnpaidOrderResource;
use App\Models\ScalevUnpaidOrder;
use Filament\Resources\Pages\ListRecords;

class ListScalevUnpaidOrders extends ListRecords
{
    protected static string $resource = ScalevUnpaidOrderResource::class;

    public function getSubheading(): ?string
    {
        return 'Pesanan di Scalev yang belum dibayar, '.ScalevUnpaidOrder::KEEP_DAYS.' hari terakhir. Follow up mengirim pembeli ke checkout baru dengan harga sekarang. Diperbarui tiap jam; yang sudah bayar atau sudah punya kelasnya hilang sendiri.';
    }
}
