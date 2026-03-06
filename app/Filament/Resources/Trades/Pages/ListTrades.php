<?php

namespace App\Filament\Resources\Trades\Pages;

use App\Filament\Pages\AdminControlCenter;
use App\Filament\Pages\TradeOperations;
use App\Filament\Resources\Trades\TradeResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrades extends ListRecords
{
    protected static string $resource = TradeResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            Action::make('tradeOperations')
                ->label('Trade Operations')
                ->icon('heroicon-o-squares-plus')
                ->url(TradeOperations::getUrl())
                ->visible(fn (): bool => $user instanceof User && $user->isPlayer()),
            Action::make('adminControlCenter')
                ->label('Admin Control Center')
                ->icon('heroicon-o-command-line')
                ->url(AdminControlCenter::getUrl())
                ->visible(fn (): bool => $user instanceof User && $user->isAdmin()),
            CreateAction::make()
                ->visible(fn (): bool => $user instanceof User && $user->isPlayer()),
        ];
    }
}
