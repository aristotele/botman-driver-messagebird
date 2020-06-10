<?php

namespace BotMan\Drivers\Messagebird\Providers;

use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Studio\Providers\StudioServiceProvider;
use BotMan\Drivers\Messagebird\MessagebirdWhatsappDriver;

class MessagebirdServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!$this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes([
                __DIR__ . '/../../stubs/messagebird.php' => config_path('botman/messagebird.php'),
            ]);

            $this->mergeConfigFrom(__DIR__ . '/../../stubs/messagebird.php', 'botman.messagebird');
        }
    }

    protected function loadDrivers()
    {
        DriverManager::loadDriver(MessagebirdWhatsappDriver::class);
    }

    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
