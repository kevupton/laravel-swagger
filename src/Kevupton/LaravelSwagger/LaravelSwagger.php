<?php namespace Kevupton\LaravelSwagger;

use Illuminate\Database\Eloquent\Model;
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
}