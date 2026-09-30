<?php

namespace App\Filament\Resources\RafflePrizes\Pages;

use App\Filament\Resources\RafflePrizes\RafflePrizeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRafflePrizes extends ListRecords
{
    protected static string $resource = RafflePrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
