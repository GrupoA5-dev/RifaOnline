<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $title = 'Painel do organizador';

    protected static ?string $navigationLabel = 'Início';

    protected string $view = 'filament.organizer.pages.dashboard';
}
