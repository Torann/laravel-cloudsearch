<?php

return [

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
        'region' => env('CLOUDSEARCH_REGION', 'us-east-1'),

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

    'domain_name' => 'lulebe-staging',

    /*
    |--------------------------------------------------------------------------
    | Index Fields
    |--------------------------------------------------------------------------
    |
    | This is used to specify your index fields and their data types.
    |
    */

    'fields' => [

        // General
        'id' => 'literal',
        'locale' => 'literal',
        'category_id' => 'literal',
        'status' => 'literal',
        'slug' => 'literal',
        'image_file_name' => 'literal',
        'tags' => 'literal-array',

        // Articles
        'user_id' => 'literal',
        'featured' => 'literal',
        'title' => 'text',
        'url' => 'literal',
        'source' => 'text',
        'excerpt' => 'text',

        // Plants
        'parent_id' => 'literal',
        'name' => 'text',
        'common_names' => 'text-array',
        'botanical_name' => 'text',
        'ph' => 'literal-array',
        'zones' => 'literal-array',
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
