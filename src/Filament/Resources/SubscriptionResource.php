<?php

namespace Mhmadahmd\Filasaas\Filament\Resources;

use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Mhmadahmd\Filasaas\Filament\Resources\SubscriptionResource\Pages;
use Mhmadahmd\Filasaas\Models\Subscription;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::ReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('subscriber_type')
                    ->label('Subscriber Type')
                    ->options([
                        'App\Models\User' => 'User',
                    ])
                    ->required(),
                Forms\Components\Select::make('subscriber_id')
                    ->label('Subscriber')
                    ->relationship('subscriber', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Starts At'),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Ends At'),
                Forms\Components\DateTimePicker::make('trial_ends_at')
                    ->label('Trial Ends At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subscriber.name')
                    ->label('Subscriber')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? reset($state)) : $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Subscription $record): string => match (true) {
                        $record->active() => 'success',
                        $record->canceled() => 'danger',
                        $record->ended() => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (Subscription $record): string => match (true) {
                        $record->active() => 'Active',
                        $record->canceled() => 'Canceled',
                        $record->ended() => 'Ended',
                        $record->onTrial() => 'On Trial',
                        default => 'Inactive',
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active')
                    ->query(fn ($query) => $query->findActive()),
                Tables\Filters\Filter::make('canceled')
                    ->label('Canceled')
                    ->query(fn ($query) => $query->whereNotNull('canceled_at')),
                Tables\Filters\Filter::make('ended')
                    ->label('Ended')
                    ->query(fn ($query) => $query->findEndedPeriod()),
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->cancel()),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }
}
