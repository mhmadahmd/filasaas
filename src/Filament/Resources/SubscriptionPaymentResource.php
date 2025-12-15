<?php

namespace Mhmadahmd\Filasaas\Filament\Resources;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Mhmadahmd\Filasaas\Filament\Actions\ApprovePaymentAction;
use Mhmadahmd\Filasaas\Filament\Resources\SubscriptionPaymentResource\Pages;
use Mhmadahmd\Filasaas\Models\SubscriptionPayment;
use UnitEnum;

class SubscriptionPaymentResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::CreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('subscription_id')
                    ->label('Subscription')
                    ->relationship('subscription', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('gateway')
                    ->label('Gateway')
                    ->options([
                        SubscriptionPayment::GATEWAY_CASH => 'Cash',
                        SubscriptionPayment::GATEWAY_STRIPE => 'Stripe',
                        SubscriptionPayment::GATEWAY_PAYPAL => 'PayPal',
                        SubscriptionPayment::GATEWAY_CUSTOM => 'Custom',
                    ])
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        SubscriptionPayment::METHOD_CASH => 'Cash',
                        SubscriptionPayment::METHOD_BANK_TRANSFER => 'Bank Transfer',
                        SubscriptionPayment::METHOD_ONLINE => 'Online',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('currency')
                    ->label('Currency')
                    ->default('USD')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        SubscriptionPayment::STATUS_PENDING => 'Pending',
                        SubscriptionPayment::STATUS_PAID => 'Paid',
                        SubscriptionPayment::STATUS_FAILED => 'Failed',
                        SubscriptionPayment::STATUS_REFUNDED => 'Refunded',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subscription.plan.name')
                    ->label('Plan')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? reset($state)) : $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'gray',
                        'stripe' => 'success',
                        'paypal' => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('requires_approval')
                    ->label('Requires Approval')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gateway')
                    ->label('Gateway')
                    ->options([
                        SubscriptionPayment::GATEWAY_CASH => 'Cash',
                        SubscriptionPayment::GATEWAY_STRIPE => 'Stripe',
                        SubscriptionPayment::GATEWAY_PAYPAL => 'PayPal',
                        SubscriptionPayment::GATEWAY_CUSTOM => 'Custom',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        SubscriptionPayment::STATUS_PENDING => 'Pending',
                        SubscriptionPayment::STATUS_PAID => 'Paid',
                        SubscriptionPayment::STATUS_FAILED => 'Failed',
                        SubscriptionPayment::STATUS_REFUNDED => 'Refunded',
                    ]),
                Tables\Filters\Filter::make('pending_approval')
                    ->label('Pending Approval')
                    ->query(fn ($query) => $query->pendingApproval()),
            ])
            ->recordActions([
                ApprovePaymentAction::make()
                    ->visible(fn (SubscriptionPayment $record) => $record->requires_approval && $record->isPending()),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->requires_approval && $record->isPending()) {
                                    $record->approve(auth()->user());
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPayments::route('/'),
            'view' => Pages\ViewSubscriptionPayment::route('/{record}'),
        ];
    }
}
