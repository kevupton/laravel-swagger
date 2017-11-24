# laravel-swagger
Swagger Annotations Generator for Laravel

### Uses Swagger PHP and laravel to generate the Swagger JSON
***

###Install
> composer require kevupton/laravel-swagger

***
##Table Of Contents

> *  [MODEL INTEGRATION](#model-integration)
>  * [Usage](#usage)
>  * [Output](#output)
> * [CONTROLLER INTEGRATION](#controller-integration)
>  * [Getting Started](#getting-started)
>  * [Example](#example)
>  * [Keys](#keys)
>  * [Example Output](#example-output)
> * [Custom Handler](#custom-handler)
>  * [Definition](#definition)
>  * [Implementation](#implementation)
> * [Overriding Values](#overriding-values)
> * [Seperate Container Class](#seperate-container-class)

***

##MODEL INTEGRATION

###Usage
> `\Kevupton\LaravelSwagger\scan($path, $models);`

Use `\Kevupton\LaravelSwagger\scan` instead of `\Swagger\scan`: (instead of swagger-php's scan method)
```PHP
/** @var Swagger\Annotations\Swagger $swagger */
$swagger = \Kevupton\LaravelSwagger\scan(app_path('location'), [
    'models' => [
        /** All models go in here */
        \App\Models\User::class
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

###Output
```
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


##CONTROLLER INTEGRATION
The controller integration allows you to define a generic output with customized fields for each Controller. It will require a parent controller to define the shape of each output response.

###Getting Started
The first things that you need to look into grouping your routes, and having a parent Controller define each group.

#####The Routes
```php
Route::get('/v1/test', ['uses' => 'TestController@index', 'as' => 'v1.test.index']);
Route::get('/v1/foo', ['uses' => 'FooController@index', 'as' => 'v1.foo.index']);
Route::get('/v1/bar', ['uses' => 'BarController@index', 'as' => 'v1.bar.index']);
```

Now each route index shares the same functionality, which is to display a list of results using pagination.
***
*I have a custom package to help with Controller functionality which can be found at [Ethereal](http://github.com/kevupton/ethereal)'s [Resource Trait](https://github.com/kevupton/ethereal/wiki/resourcetrait)*
***
In order to create these dynamic methods, the best way is to create a Parent Controller, or `BaseController` which each extends.

###Example

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

`public static function getSwaggerRoutes()` is the function that returns the structure of the route requests.

`return []` is the array containing the route `index` and the value `DynamicMethod::GET`

####Route Matching
the route key: `index` in the example about defines a generic search in the route names that end with that value. Example: `index` will match `v1.test.index` but not `v1.index.test`. It has to end with that value. So `test.index` will also match `v1.test.index`

If it matches then it will look for each key on the controller.

###Keys
> Keys are defined with **{{keyname}}**

In the example above you can see the example keys:
```php
				'tags' => ['{{tag}}'],
				'summary' => '{{summary}}',
				'value' => '{{response}}',
```
The default behavior will search for these values on each Controller. This behavior can be modified via [Editing the Default Behaviour](#custom-handler)

####The Default Behavior
The handler will search the *Child Controller* for each value:

```php
class TestController {
	public static $tag = "my custom tag";
	public static $summary = "how awesome is this";
	public static $response = "#/definitions/Response"
```
So for the test controller it will place those variables into each key. **Note how they are static**

This will give an example output of:

###Example Output
This is one of the paths located in the swagger json output.
```json
/v1/test: {
	get: {
		tags: [
			"my custom tag"
		],
		summary: "how awesome is this",
		parameters: [
			{
				name: "page",
				in: "query",
				description: "the page number",
				required: false,
				type: "string"
			},
		],
		responses: {
			200: {
			description: "test",
			schema: {
				$ref: "#/definitions/dynamic-definition-1"
			}
		}
	}
},
```

##CUSTOM HANDLER
###Definition
Extend the handler class and implement the handle method
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

In order to implement it just add it to the custom controller

###Implementation

```php
use App\Handlers\CustomHandler;

class BaseController extends Controller {
	
	//The method for defining the custom handler
	public static function getSwaggerHandler() {
		return CustomHandler::class;
	}
}

```

##Overriding Values

All values can be overridden from the *Child Controller*.


##Seperate Container Class
A seperate class can be used for implementing the definition of the methods:
`getSwaggerRoutes` and `getSwaggerHandler`

Just Extend: `\Kevupton\LaravelSwagger\MethodContainer` class.
*Note: the getSwaggerMethods is yet to be implemented.*

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

Then on the controller reference your extension by using a static property: `swagger_container`
```php
use App\Swagger\CustomContainer;

class BaseController extends Controller {
	public static $swagger_container = CustomContainer::class;
}
```
