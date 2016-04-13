<?php namespace Kevupton\LaravelSwagger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Kevupton\LaravelSwagger\Exceptions\DynamicMethodException;
use ReflectionClass;
use Swagger\Analysis;
use Schema;
use Swagger\Annotations\Definition;
use Swagger\Annotations\Property;
use Swagger\Context;

class LaravelSwagger {

    /** @var array The list of model classes */
    private $models = [];

    /**
     * LaravelSwagger constructor.
     *
     * @param array $models
     */
    public function __construct($models = [])
    {
        $this->models = $models;
    }

    /**
     * The handler which is called on process
     *
     * @param Analysis $analysis
     */
    public function __invoke(Analysis $analysis)
    {

        $this->load_models($analysis);

        $this->load_controllers($analysis);

    }


    /**
     * Loads the Controllers into the Swagger JSON
     *
     * @param Analysis $analysis
     */
    private function load_controllers(Analysis $analysis) {

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->getRoutes() as $route) {
            //gets the controller
            if (isset($route['action']['uses'])) {
                $controller = explode('@',$route['action']['uses']);
                $controller = $controller[0];

                list($methods, $routes, $default_handler) = MethodContainer::loadData($controller, $route['action']);

                $name = isset($route['action']['as'])? $route['action']['as']: null;

                //Calculate the direct route first
                $handler = $this->get_route_val($routes, $name, $default_handler);

                if (!is_null($handler)) {
                    $handler->handle($controller, $this);

                    $handler->method()->data('path', $route['uri']);

                    $analysis->addAnnotation($handler->method()->make(), new Context(['-', $controller]));
                }
            }
        }

    }

    /**
     * Gets all the routes that are registered
     *
     * @return array
     */
    private function getRoutes() {

        if ($this->isLumen()) {

            return app()->getRoutes();

        } else {
            $routes = \Route::getRoutes();

            $array = [];

            /** @var Route $route */
            foreach ($routes as $route) {
                foreach ($route->getMethods() as $method) {
                    $array[] = [
                        'method' => $method,
                        'uri' => $route->getPath(),
                        'action' => $route->getAction()
                    ];
                }
            }

            return $array;
        }

    }

    /**
     * Checks whether or not the application is Laravel
     *
     * @return bool
     */
    private function isLaravel() {
        return !$this->isLumen();
    }

    /**
     * Checks whether or not the application is Lumen
     *
     * @return bool
     */
    private function isLumen() {
        return class_exists('Laravel\Lumen\Application');
    }

    /**
     * Gets the DynamicMethod for the specific route value.
     *
     * @param DynamicMethod[] $routes
     * @param string $name
     * @param string $handler
     * @return DynamicHandler|null
     * @throws DynamicMethodException
     */
    private function get_route_val($routes, $name, $handler) {

        if (!is_string($name)) return null;

        /**
         * @var string $route
         * @var DynamicMethod|DynamicHandler $dynamic_method
         */
        foreach ($routes as $route => $value) {
            if (preg_match("/" . preg_quote($route, '/') . "$/", $name)) {
                if ($value instanceof DynamicMethod) {
                    return new $handler($value);
                } else if ($value instanceof DynamicHandler) {
                    return $value;
                } else {
                    throw new DynamicMethodException("Invalid Value for $route in $name");
                }
            }
        }

        return null;
    }


    /**
     * Loads the Laravel Models into the Swagger JSON
     *
     * @param Analysis $analysis
     */
    private function load_models(Analysis $analysis) {

        foreach ($this->models as $model) {
            /** @var Model $model */
            $obj = new $model();

            if ($obj instanceof Model) { //check to make sure it is a model
                $reflection = new ReflectionClass($obj);
                $with = $reflection->getProperty('with');
                $with->setAccessible(true);

                $list = Schema::getColumnListing($obj->getTable());
                $list = array_diff($list, $obj->getHidden());

                $properties = [];

                foreach ($list as $item) {
                    $properties[] = new Property([
                        'property' => $item,
                        'type' => 'string'
                    ]);
                }

                foreach ($with->getValue($obj) as $item) {
                    $class = get_class($obj->{$item}()->getModel());
                    $properties[] = new Property([
                        'property' => $item,
                        'ref' => '#/definitions/' . $class
                    ]);
                }

                $definition = new Definition([
                    'definition' => $model,
                    'properties' => $properties
                ]);

                $analysis->addAnnotation($definition, new Context(['-', $model]));
            }
        }
    }

    /**
     * Gets the models name of the last model.
     *
     * @param $model
     * @return string
     */
    private function getModelName($model) {
        return last(explode("\\", $model));
    }

    /**
     * Checks if the model exists in the array.
     *
     * @param $model
     * @return bool
     */
    public function hasModel($model) {
        return in_array($model, $this->models);
    }
}