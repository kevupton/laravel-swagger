# laravel-swagger [![kevupton/laravel-swagger](https://travis-ci.org/kevupton/laravel-swagger.svg?branch=master)](https://travis-ci.org/kevupton/laravel-swagger)
Swagger Annotations Generator for Laravel 5.0 and up.



## Introduction
This package uses the Swagger PHP library and Laravel to generate an OpenAPI 3.0-compliant JSON Specification.

This package supports Laravel 5.0 and above.

## Installation
```bash
$ composer require kevupton/laravel-swagger
```


# Table Of Contents

> * [Models](#models)
> * [Controllers](#controllers)
> * [Custom Handlers](#custom-handlers)
> * [Overriding Values](#overriding-values)
> * [Seperate Container Class](#seperate-container-class)



## Models

### Usage
> `\Kevupton\LaravelSwagger\scan($path, $models);`

Define your Eloquent Models as shown below, in order for `laravel-swagger` to include in your specification:

```PHP
/** @var Swagger\Annotations\Swagger $swagger */
$swagger = \Kevupton\LaravelSwagger\scan(app_path('location'), [
    'models' => [
        /** All models go in here */
        \App\Models\User::class,
    ]
]);
```

Example model:

```PHP
class User {
  protected $table = 'users';
  protected $fillable = ['first_name', 'last_name', 'image_id'];
  protected $hidden = ['created_at', 'updated_at', 'image_id'];
  protected $with = ['image'];
  public $timestamps = true;
  
  public function image() {
    return $this->belongsTo(Image::class);
  }
}
```

### Output
```JSON
"App\\Models\\User": {
    "properties": {
        "id": {
            "type": "string"
        },
        "first_name": {
            "type": "string"
        },
        "last_name": {
            "type": "string"
        },
        "image": {
            "$ref": "#/definitions/App\\Models\\Image"
        }
    }
}
```


## Controllers
Laravel-Swagger allows you to define a generic, customized output for each Controller. It requires a parent controller to define the base of each output response.

### Getting Started
For example, you have controllers `TestController`, `FooController` and `BarController` which serves API requests. 

Each of the `index` methods share similar functionality, which is to display a list of results with pagination. The router definition is as follows:


### Example Router Definition
```PHP
Route::get('/v1/test', ['uses' => 'TestController@index', 'as' => 'v1.test.index']);
Route::get('/v1/foo', ['uses' => 'FooController@index', 'as' => 'v1.foo.index']);
Route::get('/v1/bar', ['uses' => 'BarController@index', 'as' => 'v1.bar.index']);

Route::get('/v1/bar/{bar}', ['uses' => 'BarController@show', 'as' => 'v1.bar.show']);
```

***
*I have a custom package to help with Controller functionality which can be found at [Ethereal](http://github.com/kevupton/ethereal)'s [Resource Trait](https://github.com/kevupton/ethereal/wiki/resourcetrait)*
***
Since these Controllers share the same basic output, you can utilize a `BaseController` that the above Controllers may inherit from. An example `BaseController` is shown below:

### Example Base Controller

```php
<?php namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Kevupton\LaravelSwagger\DynamicMethod;
use Swagger\Annotations\Parameter;
use Swagger\Annotations\Response;

class BaseController extends Controller {

	//Define the function that returns the dynamic methods
	public static function getSwaggerRoutes() {
		return [
            'index' => DynamicMethod::GET([
				'tags' => ['{{tag}}'],
				'summary' => '{{summary}}',
				'parameters' => [
					new Parameter([
						'in' => 'query',
                        			'name' => 'page',
                        			'description' => 'the page number',
                        			'required' => false,
                        			'type' => 'string'
					])
				],
				'value' => new Response([
					'response' => 200,
					'description' => 'test',
					'ref' => '{{response}}'
				])
			])
		];
	}
}

```

`getSwaggerRoutes` is a method that defines the template structure of the specification for the above mentioned Controllers.

You would have noticed, that there are placeholder values, such as `{{response}}`, included in the above definition. These values will be replaced with the values found on each of the child Controllers. Refer to the section on [keys](#keys).

### Route Matching
Referring to the routing definition as shown [here](#example-router-definition), the key `index` refers to the route key `index` of the above Router definitions.

For example, referring to the [above](#example-router-definition), the router key `index` defined in `getSwaggerRoutes` will apply to the route `v1.bar.index` , and not `v1.bar.show`. 

Likewise, `v1.test.index` will match the above definition, but not `v1.index.test`.

### Keys
> **{{keyname}}**
**keyname** refers to the name of the static variable in your Controller, whose value it will be replaced with.
```php 
public static $keyname = 'Value that will be replaced';
```

Referring to [the example](#example-base-controller), you can see the example keys:
```php
'tags' => ['{{tag}}'],
'summary' => '{{summary}}',
'value' => '{{response}}',
```
The default handler will search the *Child Controller* for each variable of the same name, and replace the key with the values the variable contains.

### Example Child Controller

```php
class TestController {
	public static $tag = "my custom tag";
	public static $summary = "how awesome is this";
	public static $response = "#/definitions/Response"
```
### Example Output
This is one of the paths located in the swagger json output.
```json
{
	"/v1/test": {
		"get": {
			"tags": [
				"my custom tag"
			],
			"summary": "how awesome is this",
			"parameters": [
				{
					"name": "page",
					"in": "query",
					"description": "the page number",
					"required": false,
					"type": "string"
				},
			],
			"responses": {
				"200": {
				"description": "test",
				"schema": {
					"$ref": "#/definitions/dynamic-definition-1"
				}
			}
		}
	},
}
```
*NOTE* The default handler will replace the key with the *static* variable of the same name found in your Controller. You may modify this behavior in the section [Editing the Default Behaviour](#custom-handlers).

## Custom Handlers
### Definition
Should you require to change the default behavior of the default handler, you may extend the handler class, and implement the `handle` method, as shown below.
```php
<?php namespace App\Handlers;

use Kevupton\LaravelSwagger\DynamicHandler;
use Kevupton\LaravelSwagger\LaravelSwagger;
use Kevupton\LaravelSwagger\ValueContainer;

class CustomHandler extends DynamicHandler {
	
	/**
     * The handler which sets all the values in the dynamic definition.
     *
     * @param String $class the Controller class name
     * @param LaravelSwagger $LS the LaravelSwagger instance.
     * @throws DynamicHandlerException
     */
	public function handle($class, LaravelSwagger $LS) {
		//all the registered keys
		$keys = $this->method->keys();

		$key = 'response';

		//get the value of from the class
		$value = ValueContainer::getValue($class, $key);

		/**
		* Do some handling here of the value?
		*/

		//to set a registered key
		$this->method->set($key, $value);
	}
}
```

To use your new custom handler, you may define `getSwaggerHandler`, returning the `::class` of the new Custom Handler, as shown below.

### Example Custom Handler Implementation

```php
use App\Handlers\CustomHandler;

class BaseController extends Controller {
	
	//The method for defining the custom handler
	public static function getSwaggerHandler() {
		return CustomHandler::class;
	}
}

```

## Overriding Values

Should your *Child Controller* contains the definition of a static variable, overriding the parent Controller's values, the *Child Controller*'s values will take effect.


## Seperate Container Class
Instead of defining the `getSwaggerMethods`, `getSwaggerRoutes` and `getSwaggerHandler` directly in your *parent* and *child Controllers*, you may define them in a separate class. 

You may then include it in your BaseController, or any other Controllers, using the static variable `$swagger_container`. Please refer to the example below.

### Example Custom Container Implementation

```php
<?php namespace App\Swagger;

use Kevupton\LaravelSwagger\DynamicMethod;
use Kevupton\LaravelSwagger\MethodContainer;

class CustomContainer extends MethodContainer {

    /**
     * Gets
     * @return DynamicMethod[]
     */
    public function getSwaggerMethods()
    {
        // TODO: Implement getSwaggerMethods() method.
    }

    /**
     * Gets the Routes for the container
     *
     * @return DynamicMethod[]
     */
    public function getSwaggerRoutes()
    {
        // TODO: Implement getSwaggerRoutes() method.
    }

    /**
     * Gets the default Handler class
     *
     * @return string the Class Name of the DynamicHandler instance
     */
    public function getSwaggerHandler()
    {
        // TODO: Implement getSwaggerHandler() method.
    }
}
```

### Example Base Controller Implementation
```php
use App\Swagger\CustomContainer;

class BaseController extends Controller {
	public static $swagger_container = CustomContainer::class;
}
```
