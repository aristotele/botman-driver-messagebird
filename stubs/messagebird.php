<?php

return [

    /**
     * The accessKey attached in the authorization header, allows their API usage.
     * https://dashboard.messagebird.com/en/developers/access
     */
    'accessKey' => env('MESSAGEBIRD_ACCESS_KEY'),

    /**
     * The number to buy from Messagebird.
     *
     * While testing Whatsapp, use the sandbox phone number.
     * https://dashboard.messagebird.com/en/whatsapp/sandbox
     */
    'senderPhoneNumber' => env('MESSAGEBIRD_SENDER_PHONE_NUMBER'),

    /**
     * The channel configured on Messagebird Account. [Telegram, Whatsapp, ...]
     * https://dashboard.messagebird.com/en/channels
     *
     * While testing Whatsapp, use the sandbox channelId
     * https://dashboard.messagebird.com/en/whatsapp/sandbox
     */
    'channelId' => env('MESSAGEBIRD_CHANNEL_ID'),

];
