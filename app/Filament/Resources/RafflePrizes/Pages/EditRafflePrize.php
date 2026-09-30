<?php

namespace App\Filament\Resources\RafflePrizes\Pages;

use App\Filament\Resources\RafflePrizes\RafflePrizeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRafflePrize extends EditRecord
{
    protected static string $resource = RafflePrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
