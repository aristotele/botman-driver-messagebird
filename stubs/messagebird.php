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
];
