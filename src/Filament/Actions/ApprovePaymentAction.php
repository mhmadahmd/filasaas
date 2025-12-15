<?php

namespace Mhmadahmd\Filasaas\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Mhmadahmd\Filasaas\Models\SubscriptionPayment;

class ApprovePaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approve';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (SubscriptionPayment $record) {
                $record->approve(auth()->user());

                // Activate subscription if needed
                $subscription = $record->subscription;
                if ($subscription && ! $subscription->active()) {
                    $subscription->renew();
                }

                Notification::make()
                    ->title('Payment Approved')
                    ->success()
                    ->send();
            })
            ->visible(fn (SubscriptionPayment $record) => $record->requires_approval && $record->isPending());
    }
}
