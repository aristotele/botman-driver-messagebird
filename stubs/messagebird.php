<?php

return [

    /**
     * The accessKey attached in the authorization header, allows their API usage.
     * https://dashboard.messagebird.com/en/developers/access
     */
    'access_key' => env('MESSAGEBIRD_ACCESS_KEY'),

    /**
     * Enables messagebird sandbox endpoint.
     */
    'is_sandbox_enabled' => env('MESSAGEBIRD_SANDBOX', false),

    /**
     * The number bought from Messagebird.
     *
     * While testing Whatsapp, use the sandbox phone number.
     * https://dashboard.messagebird.com/en/whatsapp/sandbox
     */
    'phone_number' => env('MESSAGEBIRD_SENDER_PHONE_NUMBER'),

    /**
     * The channel configured on Messagebird Account. [Telegram, Whatsapp, ...]
     * https://dashboard.messagebird.com/en/channels
     *
     * While testing Whatsapp, use the sandbox channelId
     * https://dashboard.messagebird.com/en/whatsapp/sandbox
     */
    'channel_id' => env('MESSAGEBIRD_DEFAULT_CHANNEL_ID'),

];
