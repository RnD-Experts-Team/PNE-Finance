<?php

return [

    'client_id'     => env('QBO_CLIENT_ID'),
    'client_secret' => env('QBO_CLIENT_SECRET'),
    'redirect_uri'  => env('QBO_REDIRECT_URI'),
    'sandbox'       => env('QBO_SANDBOX', true),

    'scope'         => 'com.intuit.quickbooks.accounting',

    // For convenience we map the base URLs here
    'base_url' => env('QBO_SANDBOX', true) ? 'sandbox' : 'production',

];
