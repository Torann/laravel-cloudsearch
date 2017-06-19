<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CloudSearch State
    |--------------------------------------------------------------------------
    |
    | This is here to help with local development. It can be a little tough
    | working with CloudSearch on a local machine.
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Custom CloudSearch Client Configuration
    |--------------------------------------------------------------------------
    |
    | This array will be passed to the CloudSearch client.
    |
    */

    'config' => [
        'endpoint' => env('CLOUDSEARCH_ENDPOINT'),
        'region' => env('CLOUDSEARCH_REGION'),

        'credentials' => [
            'key'      => env('AWS_KEY'),
            'secret'   => env('AWS_SECRET')
        ],

        'version'  => '2013-01-01',
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Name
    |--------------------------------------------------------------------------
    |
    | The domain name used for the searching.
    |
    */

    'domain_name' => 'you-domain',

    /*
    |--------------------------------------------------------------------------
    | Index Fields
    |--------------------------------------------------------------------------
    |
    | This is used to specify your index fields and their data types.
    |
    */

    'fields' => [
        'id' => 'literal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | Change this if you use a different model namespace for Laravel.
    |
    */

    'model_namespace' => '\\App',

    /*
    |--------------------------------------------------------------------------
    | Support Locales
    |--------------------------------------------------------------------------
    |
    | This is used in the command line to import and map models.
    |
    */

    'support_locales' => [],

];
