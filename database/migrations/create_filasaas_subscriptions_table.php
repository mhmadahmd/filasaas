<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filasaas_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subscriber');
            $table->foreignId('plan_id')->constrained('filasaas_plans')->onDelete('cascade');
            $table->string('slug');
            $table->json('name');
            $table->json('description')->nullable();

            // Dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancels_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            // External gateway IDs
            $table->string('stripe_id')->nullable();
            $table->string('stripe_status')->nullable();
            $table->string('paddle_id')->nullable();
            $table->string('paypal_subscription_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('plan_id');
            $table->index('stripe_id');
            $table->index('paypal_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filasaas_subscriptions');
    }
};
