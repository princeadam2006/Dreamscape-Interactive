<?php

namespace App\Filament\Resources\MarketListings\Pages;

use App\Filament\Resources\MarketListings\MarketListingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageMarketListings extends ManageRecords
{
    protected static string $resource = MarketListingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
