<?php

namespace Mhmadahmd\Filasaas\Filament\Resources;

use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Mhmadahmd\Filasaas\Filament\Resources\PlanResource\Pages;
use Mhmadahmd\Filasaas\Models\Plan;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Tag;

    protected static string | UnitEnum | null $navigationGroup = 'Billing';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                Schemas\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('signup_fee')
                            ->label('Signup Fee')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('USD')
                            ->required(),
                    ]),

                Schemas\Components\Section::make('Billing Period')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_period')
                            ->label('Invoice Period')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\Select::make('invoice_interval')
                            ->label('Invoice Interval')
                            ->options([
                                'day' => 'Day',
                                'week' => 'Week',
                                'month' => 'Month',
                                'year' => 'Year',
                            ])
                            ->default('month')
                            ->required(),
                    ]),

                Schemas\Components\Section::make('Trial Period')
                    ->schema([
                        Forms\Components\TextInput::make('trial_period')
                            ->label('Trial Period')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Select::make('trial_interval')
                            ->label('Trial Interval')
                            ->options([
                                'day' => 'Day',
                                'week' => 'Week',
                                'month' => 'Month',
                                'year' => 'Year',
                            ])
                            ->default('day'),
                    ]),

                Schemas\Components\Section::make('Gateway Settings')
                    ->schema([
                        Forms\Components\Toggle::make('cash_auto_approve')
                            ->label('Auto-approve Cash Payments')
                            ->default(false),
                        Forms\Components\CheckboxList::make('allowed_payment_gateways')
                            ->label('Allowed Payment Gateways')
                            ->options([
                                'cash' => 'Cash',
                                'stripe' => 'Stripe',
                                'paypal' => 'PayPal',
                            ])
                            ->default(['cash', 'stripe', 'paypal']),
                        Forms\Components\TextInput::make('stripe_price_id')
                            ->label('Stripe Price ID'),
                        Forms\Components\TextInput::make('paypal_plan_id')
                            ->label('PayPal Plan ID'),
                    ]),

                Schemas\Components\Section::make('Advanced')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('active_subscribers_limit')
                            ->label('Active Subscribers Limit')
                            ->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? reset($state)) : $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Subscribers')
                    ->counts('subscriptions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Plan $record) => $record->activate())
                    ->visible(fn (Plan $record) => ! $record->is_active),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Plan $record) => $record->deactivate())
                    ->visible(fn (Plan $record) => $record->is_active),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
