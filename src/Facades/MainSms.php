<?php

namespace Grechanyuk\MainSms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static sendSms(string|array $recipients, string $message, string $sender = null, string $run_at = null)
 * @method static sendBatch(string $sender, string $messages)
 * @method static cancelSMS(string|array $messagesId)
 * @method static checkStatus(string|array $messagesId)
 * @method static getBalance()
 */
class MainSms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mainsms';
    }
}
