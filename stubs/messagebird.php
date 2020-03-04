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
     * Put here the list of your messagebird's channels
     */
    'channels' => [

        'whatsapp' => [
            // '<phone_number_with_without_plus>' => '<channelId>'
            // '39338' => 'aadf834993'
        ]

    ],

    /**
     * The number of seconds to wait while trying to connect the host.
     */
    'connection_timeout' => 10,


    /**
     * The maximum number of seconds to allow cURL functions to execute.
     * (host connection + data transfer)
     */
    'timeout' => 15,

];
