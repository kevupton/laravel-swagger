<?php namespace Kevupton\LaravelSwagger;

use Kevupton\LaravelSwagger\Exceptions\DynamicMethodException;
use Swagger\Annotations\AbstractAnnotation;
use Swagger\Annotations\Delete;
use Swagger\Annotations\Get;
use Swagger\Annotations\Head;
use Swagger\Annotations\Operation;
use Swagger\Annotations\Options;
use Swagger\Annotations\Patch;
use Swagger\Annotations\Post;
use Swagger\Annotations\Put;

class DynamicMethod {

    /** @var DynamicMethod[] the list of registered methods */
    private static $methods = [];

    /** @var string */
    private $method;

    /** @var array all the data */
    private $data = [];

    /** @var array a link of references to method */
    private $links = [];

    /**
     * DynamicMethod constructor.
     *
     * @param string $method
     * @param array $data
     */
    public function __construct($method, array $data = array()) {
        $this->method = $method;
        $this->data = $data;
        $this->links = $this->_register_links($this->data);
    }

    /**
     * Register the reference links from the input values.
     *
     * @param array $obj
     * @return array
     */
    private function _register_links(&$obj) {
        $array = [];

        foreach ($obj as $key => &$value) {
            if (is_array($value) || $value instanceof AbstractAnnotation || $value instanceof \stdClass) {
                $array = array_merge($array, $this->_register_links($value));
            } else if (preg_match('/\{\{(.*?)\}\}/', $value, $matches)) {
                $id = $matches[1];
                $array[$id] = &$value;
            }
        }

        return $array;
    }

    /**
     * Creates a new Dynamic GET Method
     *
     * @param string $name the ID of the method to store
     * @param array $data
     * @return DynamicMethod
     * @throws DynamicMethodException
     */
    public static function GET($name, array $data = array()) {
        //check if it already exists
        if (is_null($val = self::_get($name))) {
            if (!empty($data)) { //validate the data exists
                $class = new self(Get::class, $data);
            } else {
                throw new DynamicMethodException("Dynamic Method not found and the data is empty to create one.");
            }

            return self::$methods[$name] = $class;
        }

        return $val;

    }

    /**
     * Gets the DynamicMethod if it exists, else returns NULL
     *
     * @param $name
     * @return DynamicMethod|null
     */
    private static function _get($name) {
        return (isset(self::$methods[$name]))? self::$methods[$name]: null;
    }

    /**
     * Sets the value of key.
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value) {

        $this->links[$key] = $value;

    }

    /**
     * Gets all the keys in the links array
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->links);
    }


    /**
     * Makes the Method
     *
     * @return Operation
     */
    public function make() {
        $class = $this->method;
        return new $class($this->data);
    }

    /**
     * Sets or gets the data that is to be inserted.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function data($key, $value = null) {
        if (is_null($value)) {
            return isset($this->data[$key])? $this->data[$key]: null;
        } else {
            return $this->data[$key] = $value;
        }
    }
}