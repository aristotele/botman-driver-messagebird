<?php

namespace BotMan\Drivers\Messagebird\Providers;

use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Messagebird\MessagebirdDriver;
use BotMan\Studio\Providers\StudioServiceProvider;

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
        DriverManager::loadDriver(MessagebirdDriver::class);
    }

    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
