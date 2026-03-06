<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('user.username')
                    ->label('Admin')
                    ->searchable(),
                TextColumn::make('targetUser.username')
                    ->label('Target user')
                    ->toggleable(),
                TextColumn::make('targetItem.name')
                    ->label('Target item')
                    ->toggleable(),
                TextColumn::make('meta.reason')
                    ->label('Reason')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(fn (): array => AuditLog::query()
                        ->select('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'targetUser', 'targetItem']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->isAdmin()
            && auth()->user()->can('ViewAny:AuditLog');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuditLogs::route('/'),
        ];
    }
}
