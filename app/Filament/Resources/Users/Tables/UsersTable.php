<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->since()
                    ->placeholder('Not verified')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->relationship('roles', 'name')
                    ->multiple(),
                TernaryFilter::make('email_verified_at')
                    ->label('Verified')
                    ->nullable(),
                Filter::make('search_recent')
                    ->label('Created last 30 days')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('assignItem')
                    ->label('Assign Item')
                    ->icon('heroicon-o-gift')
                    ->form([
                        Select::make('item_id')
                            ->label('Item')
                            ->options(fn (): array => Item::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        Toggle::make('locked')
                            ->label('Lock this item')
                            ->default(false),
                        Textarea::make('reason')
                            ->rows(3)
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (User $record, array $data): void {
                        DB::transaction(function () use ($data, $record): void {
                            $actor = auth()->user();

                            if (! $actor instanceof User) {
                                abort(403);
                            }

                            $inventoryItem = InventoryItem::query()->create([
                                'user_id' => $record->id,
                                'item_id' => (int) $data['item_id'],
                                'locked' => (bool) ($data['locked'] ?? false),
                            ]);

                            AuditLog::query()->create([
                                'user_id' => $actor->id,
                                'action' => 'item.assigned',
                                'target_user_id' => $record->id,
                                'target_item_id' => $inventoryItem->item_id,
                                'meta' => [
                                    'reason' => (string) $data['reason'],
                                ],
                                'created_at' => now(),
                            ]);

                            FilamentNotification::make()
                                ->title('Inventory Item Assigned')
                                ->body('An admin assigned a new item to your inventory.')
                                ->info()
                                ->viewData([
                                    'context' => [
                                        'inventory_item_id' => $inventoryItem->id,
                                        'item_id' => $inventoryItem->item_id,
                                        'reason' => (string) $data['reason'],
                                    ],
                                    'notification_type' => 'inventory.item_assigned',
                                ])
                                ->sendToDatabase($record, isEventDispatched: true);
                        });

                        FilamentNotification::make()
                            ->success()
                            ->title('Item assigned successfully.')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
