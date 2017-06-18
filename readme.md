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

### Fields

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

#### `search:import <model>`

Import all the entries in an Eloquent model. This will also initialize the model's map if one is not already set.

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

## Indexing

Once you have added the `LaravelCloudSearch\Eloquent\Searchable` trait to a model, all you need to do is save a model instance and it will automatically be added to your index.

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

## Multilingual

> This feature is experimental

Laravel CloudSearch can support multiple languages by appending the language code to the index type, so when the system performs a search it will only look for data that is on in the current system locale suffixed index type. For this to work the model needs to use the `LaravelCloudSearch\Eloquent\Localized` trait or something similar to it and model needs to have the filed `locale`.