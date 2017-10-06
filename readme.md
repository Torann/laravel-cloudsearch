# Laravel CloudSearch

[![Latest Stable Version](https://poser.pugx.org/torann/laravel-cloudsearch/v/stable.png)](https://packagist.org/packages/torann/laravel-cloudsearch)
[![Total Downloads](https://poser.pugx.org/torann/laravel-cloudsearch/downloads.png)](https://packagist.org/packages/torann/laravel-cloudsearch)
[![Patreon donate button](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/torann)
[![Donate weekly to this project using Gratipay](https://img.shields.io/badge/gratipay-donate-yellow.svg)](https://gratipay.com/~torann)
[![Donate to this project using Flattr](https://img.shields.io/badge/flattr-donate-yellow.svg)](https://flattr.com/profile/torann)
[![Donate to this project using Paypal](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4CJA2A97NPYVU)

Index and search Laravel models on Amazon's CloudSearch. To get started, you should have a basic knowledge of how CloudSearch works.

## Installation

### Composer

From the command line run:

```
$ composer require torann/laravel-cloudsearch
```

### Laravel

Once installed you need to register the service provider with the application. Open up `config/app.php` and find the `providers` key.

``` php
'providers' => [

    LaravelCloudSearch\LaravelCloudSearchServiceProvider::class,

]
```

### Lumen

For Lumen register the service provider in `bootstrap/app.php`.

``` php
$app->register(LaravelCloudSearch\LaravelCloudSearchServiceProvider::class);
```

### Publish the configurations

Run this on the command line from the root of your project:

```
$ php artisan vendor:publish --provider="LaravelCloudSearch\LaravelCloudSearchServiceProvider" --tag=config
```

A configuration file will be publish to `config/cloud-search.php`.


### Migration

The package uses a batch queue system for updating the documents on AWS. This is done to help reduce the number of calls made to the API (will save money in the long run).

```bash
php artisan vendor:publish --provider="LaravelCloudSearch\LaravelCloudSearchServiceProvider" --tag=migrations
```

Run this on the command line from the root of your project to generate the table for storing currencies:

```bash
$ php artisan migrate
```

## Fields

The better help manage fields, the package ships with a simple field management command. This is completely optional, as you can manage them in the AWS console.

> **NOTE:** If you choose not to use this command to manage or setup your fields, you will still need to add the field `searchable_type` as a `literal`. This is used to store the model type.

They can be found in the `config/cloud-search.php` file under the `fields` property:

```php
'fields' => [
    'title' => 'text',
    'status' => 'literal',
],
```

## Artisan Commands

#### `search:fields`

Initialize an Eloquent model map.

#### `search:index <model>`

Name or comma separated names of the model(s) to index.

Arguments:

```
 model               Name or comma separated names of the model(s) to index
```

#### `search:flush <model>`

Flush all of the model documents from the index.

Arguments:

```
 model               Name or comma separated names of the model(s) to index
```

#### `search:queue`

Reduces the number of calls made to the CloudSearch server by queueing the updates and deletes.

## Indexing

Once you have added the `LaravelCloudSearch\Eloquent\Searchable` trait to a model, all you need to do is save a model instance and it will automatically be added to your index when the `search:queue` command is ran.

```php
$post = new App\Post;

// ...

$post->save();
```

> **Note**: if the model document has already been indexed, then it will simply be updated. If it does not exist, it will be added.

## Updating Documents

To update an index model, you only need to update the model instance's properties and `save`` the model to your database. The package will automatically persist the changes to your search index:

```php
$post = App\Post::find(1);

// Update the post...

$post->save();
```

## Removing Documents

To remove a document from your index, simply `delete` the model from the database. This form of removal is even compatible with **soft deleted** models:

```php
$post = App\Post::find(1);

$post->delete();
```

## Searching

You may begin searching a model using the `search` method. The search method accepts a single string that will be used to search your models. You should then chain the `get` method onto the search query to retrieve the Eloquent models that match the given search query:

```php
$posts = App\Post::search('Kitten fluff')->get();
```

Since package searches return a collection of Eloquent models, you may even return the results directly from a route or controller and they will automatically be converted to JSON:

```php
use Illuminate\Http\Request;

Route::get('/search', function (Request $request) {
    return App\Post::search($request->search)->get();
});
```

## Pagination

In addition to retrieving a collection of models, you may paginate your search results using the `paginate` method. This method will return a `Paginator` instance just as if you had paginated a traditional Eloquent query:

```php
$posts = App\Post::search('Kitten fluff')->paginate();
```
You may specify how many models to retrieve per page by passing the amount as the first argument to the `paginate` method:

```php
$posts = App\Post::search('Kitten fluff')->paginate(15);
```
Once you have retrieved the results, you may display the results and render the page links using Blade just as if you had paginated a traditional Eloquent query:

```blade
<div class="container">
    @foreach ($posts as $post)
        {{ $post->title }}
    @endforeach
</div>

{{ $posts->links() }}
```

## Basic Builder Usage

Initialize a builder instance:

```php
$query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();
```

You can chain query methods like so:

```php
$query->phrase('ford')
    ->term('National Equipment', 'seller')
    ->range('year', '2010');
```

use the `get()` or `paginate()` methods to submit query and retrieve results from AWS.

```php
$results = $query->get();
```

In the example above we did not set the search type, so this means the results that are returned will match any document on CloudSearch domain. To refine you search to certain model, either use the model like shown in the example previously or use the `searchableType()` method to set the class name of the model (this is done automatically in the model instance call):

```php
$query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

$results = $query->searchableType(\App\LawnMower::class)
    ->term('honda', 'name')
    ->get();
```

### Search Query Operators and Nested Queries

You can use the `and`, `or`, and `not` operators to build compound and nested queries. The corresponding `and()`, `or()`, and `not()` methods expect a closure as their argument. You can chain all available methods as well nest more sub-queries inside of closures.

```php
$query->or(function($builder) {
    $builder->phrase('ford')
        ->phrase('truck');
});
```

## Queue

The help reduce the number of bulk requests made to the CloudSearch endpoint (because they cost) a queue system is used. This can be set in Laravel [Task Scheduling](https://laravel.com/docs/5.4/scheduling). You can decide how often it is ran using the scheduled task frequency options. Please note this uses the DB to function.

Example of the task added to `/app/Console/Kernel.php`:

```php
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('search:queue')->everyTenMinutes();
    }
```

## Multilingual

> This feature is experimental

Laravel CloudSearch can support multiple languages by appending the language code to the index type, so when the system performs a search it will only look for data that is on in the current system locale suffixed index type. For this to work the model needs to use the `LaravelCloudSearch\Eloquent\Localized` trait or something similar to it.
