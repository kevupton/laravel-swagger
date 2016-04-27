<?php namespace Kevupton\LaravelSwagger;

use Illuminate\Database\Eloquent\Model;
use Kevupton\LaravelSwagger\Exceptions\DynamicHandlerException;
use Kevupton\LaravelSwagger\Exceptions\DynamicMethodException;

class DynamicHandler {

    /** @var DynamicMethod */
    private $method;

    /**
     * DynamicHandler constructor.
     *
     * @param DynamicMethod $method
     * @throws DynamicMethodException
     */
    public function __construct(DynamicMethod $method) {

        if (is_null($method)) throw new DynamicMethodException("Method cannot be null.");

        $this->method = $method;
    }

    /**
     * The handler which sets all the values in the dynamic definition.
     *
     * @param String $class the Controller class name
     * @param LaravelSwagger $LS the LaravelSwagger instance.
     * @throws DynamicHandlerException
     */
    public function handle($class, LaravelSwagger $LS) {

        /**
         *************************************
         *         Default Behaviour         *
         *************************************
         *
         * Loops through all of the linked keys
         */
        foreach ($this->method->keys() as $key) {
            /** @var mixed $value the value associated with the specific key */
            $value = ValueContainer::getValue($class, $key);

            if (is_string($value)) { //if its a string of a class
                //if it is a model that has been registered.
                if (is_subclass_of($value, Model::class) && $LS->hasModel($value)) {
                    $value = "#/definitions/$value";
                }
            }

            //if there is no value then throw an exception
            if (is_null($value)) {
                throw new DynamicHandlerException("$key value is NULL");
            }

            $this->method->set($key, $value);
        }

    }

    /**
     * Gets the DynamicMethod associated with the Handle.
     *
     * @return DynamicMethod
     */
    public function method() {
        return $this->method;
    }

}