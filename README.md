# laravel-swagger
Swagger Annotations Generator for Laravel

### Uses Swagger PHP and laravel to generate the Swagger JSON ###
***

###Install###
`
composer require kevupton/laravel-swagger
`

###Usage###
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

###Output###
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


