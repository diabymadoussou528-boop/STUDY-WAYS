<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire premium subscriptions past their end date';

    public function handle(SubscriptionService $service): int
    {
        $count = $service->expireDueSubscriptions();
        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
